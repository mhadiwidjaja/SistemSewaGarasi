<?php

namespace App\Services;

use App\Enums\GarageType;
use App\Enums\VehicleType;
use App\Models\Garage;
use App\Models\Rental;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Garage availability and new rentals: enforces which vehicle fits which garage,
 * that a garage is free for the whole period, and computes the fees.
 */
class RentalService
{
    /** Folder on the private disk where payment proof images are stored. */
    public const PAYMENT_PROOF_FOLDER = 'payment-proofs';

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
     * Preview an order: its end date and every available garage with the fees it would cost.
     *
     * @return array{end_date: CarbonImmutable, garages: Collection<int, array{garage: Garage, rental_fee: int, charging_fee: int}>}
     */
    public function quote(RentalOrder $order): array
    {
        $endDate = $this->endDate($order);
        $garages = $this->availableGarages($order->garageType, $order->startDate, $endDate)
            ->map(fn (Garage $garage) => ['garage' => $garage] + $this->fees($garage, $order));

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
            $this->ensureGarageFitsVehicle($garage, $order->vehicleType);
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
     * Get the last day of the order's rental period.
     */
    private function endDate(RentalOrder $order): CarbonImmutable
    {
        return $this->calculator->endDate($order->rentalType, $order->startDate, $order->days);
    }

    /**
     * Compute the rental fee, plus the charging fee when an electric car is parked in an electric car garage.
     *
     * @return array{rental_fee: int, charging_fee: int}
     */
    private function fees(Garage $garage, RentalOrder $order): array
    {
        $chargesBattery = $garage->type === GarageType::ElectricCar && $order->vehicleType->isElectric();

        return [
            'rental_fee' => $this->calculator->rentalFee($order->rentalType, $garage->daily_price, $order->days),
            'charging_fee' => $chargesBattery
                ? $this->calculator->chargingFee($order->batteryCapacityKwh, $order->batteryLeftKwh, $garage->charging_price_per_kwh)
                : 0,
        ];
    }

    /**
     * @throws ValidationException
     */
    private function ensureGarageFitsVehicle(Garage $garage, VehicleType $vehicleType): void
    {
        if (! in_array($garage->type, $vehicleType->allowedGarageTypes(), true)) {
            throw ValidationException::withMessages([
                'garage_id' => "{$garage->type->label()} tidak dapat digunakan untuk {$vehicleType->label()}.",
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
}
