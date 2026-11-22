<?php

namespace App\Services;

use App\Enums\GarageType;
use App\Enums\RentalType;
use App\Models\Garage;
use App\Models\Rental;
use App\Models\RentalExtension;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Garage availability, new rentals and extensions: enforces which vehicle fits which garage,
 * that a garage is free for the whole period, and computes the fees.
 */
class RentalService
{
    /** Folder on the private disk where payment proof images are stored. */
    private const PAYMENT_PROOF_FOLDER = 'payment-proofs';

    /**
     * @param  RentalCalculator  $calculator  The rental date and fee formulas.
     */
    public function __construct(private RentalCalculator $calculator) {}

    /**
     * Get the garages of a type that have no rental overlapping the period, in code order.
     *
     * @return Collection<int, Garage>
     */
    public function availableGarages(GarageType $type, CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        return Garage::where('type', $type)->availableBetween($start, $end)->orderBy('code')->get();
    }

    /**
     * Preview an order: its end date and, once the vehicle and garage type are chosen,
     * every available garage with the fees it would cost. The charging fee and total are null
     * while an electric car's battery data is still missing.
     *
     * @return array{end_date: CarbonImmutable, garages: Collection<int, array{garage: Garage, rental_fee: int, charging_fee: ?int, total_fee: ?int}>|null}
     */
    public function quote(RentalOrder $order): array
    {
        $endDate = $this->endDate($order);

        if ($order->vehicleType === null || $order->garageType === null) {
            return ['end_date' => $endDate, 'garages' => null];
        }

        $garages = $this->availableGarages($order->garageType, $order->startDate, $endDate)
            ->map(function (Garage $garage) use ($order) {
                $fees = $this->fees($garage, $order);

                $totalFee = $fees['charging_fee'] === null ? null : $fees['rental_fee'] + $fees['charging_fee'];

                return ['garage' => $garage, ...$fees, 'total_fee' => $totalFee];
            });

        return ['end_date' => $endDate, 'garages' => $garages];
    }

    /**
     * Save a new rental after re-checking that the garage fits the vehicle and is still free.
     *
     * The garage row is locked while checking, so two renters cannot book it at the same time.
     *
     * @throws ValidationException When the garage doesn't fit the vehicle or is no longer available.
     */
    public function createRental(User $renter, RentalOrder $order, UploadedFile $paymentProof): Rental
    {
        $endDate = $this->endDate($order);

        return DB::transaction(function () use ($renter, $order, $paymentProof, $endDate) {
            $garage = Garage::lockForUpdate()->findOrFail($order->garageId);
            $this->ensureGarageMatchesOrder($garage, $order);
            $this->ensureGarageAvailable($garage, $order->startDate, $endDate);

            return $renter->rentals()->create([
                'garage_id' => $garage->id,
                'vehicle_type' => $order->vehicleType,
                'plate_number' => $order->plateNumber,
                'vehicle_brand_type' => $order->vehicleBrandType,
                'battery_capacity_kwh' => $order->batteryCapacityKwh,
                'battery_left_kwh' => $order->batteryLeftKwh,
                'rental_type' => $order->rentalType,
                'start_date' => $order->startDate,
                'end_date' => $endDate,
                'payment_proof_path' => $paymentProof->store(self::PAYMENT_PROOF_FOLDER),
                ...$this->fees($garage, $order),
            ]);
        });
    }

    /**
     * Preview an extension. It starts the day after the current end date and follows the same
     * rules as a new rental: daily up to (start month length − 1) days, or monthly for the
     * length of its start month at 25 × the daily price. There is no charging fee.
     *
     * @param  int|null  $days  Number of days; required for daily, ignored for monthly.
     * @return array{start_date: CarbonImmutable, end_date: CarbonImmutable, fee: int}
     *
     * @throws ValidationException When the garage is already booked in the added period.
     */
    public function quoteExtension(Rental $rental, RentalType $type, ?int $days): array
    {
        $startDate = $rental->extensionStartDate();
        $endDate = $this->calculator->endDate($type, $startDate, $days);
        $this->ensureGarageFreeForExtension($rental->garage, $startDate, $endDate);

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'fee' => $this->calculator->rentalFee($type, $rental->garage->daily_price, $days),
        ];
    }

    /**
     * Extend a rental: log the extension with its fee and proof, and move the rental's end date.
     *
     * The garage row is locked while checking that nobody booked it for the added days.
     *
     * @param  int|null  $days  Number of days; required for daily, ignored for monthly.
     *
     * @throws ValidationException When the garage is already booked in the added period.
     */
    public function extendRental(Rental $rental, RentalType $type, ?int $days, UploadedFile $paymentProof): RentalExtension
    {
        return DB::transaction(function () use ($rental, $type, $days, $paymentProof) {
            Garage::lockForUpdate()->findOrFail($rental->garage_id);
            $extension = $this->quoteExtension($rental, $type, $days);

            $record = $rental->extensions()->create([
                'rental_type' => $type,
                'previous_end_date' => $rental->end_date,
                'new_end_date' => $extension['end_date'],
                'fee' => $extension['fee'],
                'payment_proof_path' => $paymentProof->store(self::PAYMENT_PROOF_FOLDER),
            ]);
            $rental->update(['end_date' => $extension['end_date']]);

            return $record;
        });
    }

    /**
     * Get the last day of the order's rental period.
     */
    private function endDate(RentalOrder $order): CarbonImmutable
    {
        return $this->calculator->endDate($order->rentalType, $order->startDate, $order->days);
    }

    /**
     * Compute the rental fee, plus the charging fee when an electric car is parked in an electric car garage.
     * The charging fee is null when the battery data needed for it is still missing (only while quoting).
     *
     * @return array{rental_fee: int, charging_fee: ?int}
     */
    private function fees(Garage $garage, RentalOrder $order): array
    {
        return [
            'rental_fee' => $this->calculator->rentalFee($order->rentalType, $garage->daily_price, $order->days),
            'charging_fee' => $this->chargingFee($garage, $order),
        ];
    }

    /**
     * Get the fee to charge the battery to full: 0 unless an electric car is in an electric car garage,
     * null when its battery data is still missing.
     */
    private function chargingFee(Garage $garage, RentalOrder $order): ?int
    {
        if ($garage->type !== GarageType::ElectricCar || ! $order->vehicleType->isElectric()) {
            return 0;
        }
        if ($order->batteryCapacityKwh === null || $order->batteryLeftKwh === null) {
            return null;
        }

        return $this->calculator->chargingFee($order->batteryCapacityKwh, $order->batteryLeftKwh, $garage->charging_price_per_kwh);
    }

    /**
     * The garage must fit the vehicle and be of the garage type the renter chose (whose fees were shown).
     *
     * @throws ValidationException
     */
    private function ensureGarageMatchesOrder(Garage $garage, RentalOrder $order): void
    {
        if (! in_array($garage->type, $order->vehicleType->allowedGarageTypes(), true)) {
            throw ValidationException::withMessages([
                'garage_id' => "{$garage->type->label()} tidak dapat digunakan untuk {$order->vehicleType->label()}.",
            ]);
        }

        if ($garage->type !== $order->garageType) {
            throw ValidationException::withMessages([
                'garage_id' => "Garasi {$garage->code} bukan {$order->garageType->label()}.",
            ]);
        }
    }

    /**
     * @throws ValidationException
     */
    private function ensureGarageAvailable(Garage $garage, CarbonImmutable $start, CarbonImmutable $end): void
    {
        if (! $garage->isAvailableBetween($start, $end)) {
            throw ValidationException::withMessages([
                'garage_id' => "Garasi {$garage->code} sudah tidak tersedia, silakan pilih garasi lain.",
            ]);
        }
    }

    /**
     * @throws ValidationException
     */
    private function ensureGarageFreeForExtension(Garage $garage, CarbonImmutable $start, CarbonImmutable $end): void
    {
        if (! $garage->isAvailableBetween($start, $end)) {
            throw ValidationException::withMessages([
                'end_date' => "Garasi {$garage->code} sudah dipesan pada periode tersebut.",
            ]);
        }
    }
}
