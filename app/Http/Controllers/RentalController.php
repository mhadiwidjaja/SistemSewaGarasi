<?php

namespace App\Http\Controllers;

use App\Enums\GarageType;
use App\Enums\RentalType;
use App\Enums\VehicleType;
use App\Http\Requests\QuoteRentalRequest;
use App\Http\Requests\StoreRentalRequest;
use App\Services\RentalCalculator;
use App\Services\RentalService;
use App\Support\Format;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The renter's garage rentals: history, the new rental form, its live quote and saving it.
 */
class RentalController extends Controller
{
    /**
     * Show the logged-in renter's rental history, newest first (S09).
     */
    public function index(Request $request): View
    {
        $rentals = $request->user()->rentals()
            ->with(['garage', 'extensions'])
            ->latest()
            ->get();

        return view('rentals.index', ['rentals' => $rentals]);
    }

    /**
     * Show the new rental form (S03).
     */
    public function create(RentalCalculator $calculator): View
    {
        $today = CarbonImmutable::today();

        return view('rentals.create', [
            'vehicleTypes' => VehicleType::cases(),
            'rentalTypes' => RentalType::cases(),
            'garageTypes' => GarageType::cases(),
            'vehicleRules' => $this->vehicleRules(),
            'today' => $today->toDateString(),
            'maxDailyDays' => $calculator->maxDailyDays($today),
        ]);
    }

    /**
     * Return the end date, the daily limit and the available garages with their fees, as JSON for the form.
     */
    public function quote(QuoteRentalRequest $request, RentalService $rentalService): JsonResponse
    {
        $quote = $rentalService->quote($request->order());

        return response()->json([
            'max_days' => $request->maxDailyDays(),
            'end_date' => Format::date($quote['end_date']),
            'garages' => $quote['garages']->map(fn (array $option) => [
                'id' => $option['garage']->id,
                'code' => $option['garage']->code,
                'daily_price' => Format::rupiah($option['garage']->daily_price).' / hari',
                'rental_fee' => Format::rupiah($option['rental_fee']),
                'charging_fee' => $option['charging_fee'] > 0 ? Format::rupiah($option['charging_fee']) : null,
                'total_fee' => Format::rupiah($option['rental_fee'] + $option['charging_fee']),
            ])->values(),
        ]);
    }

    /**
     * Save the rental and show it in the history.
     */
    public function store(StoreRentalRequest $request, RentalService $rentalService): RedirectResponse
    {
        $rental = $rentalService->createRental($request->user(), $request->order(), $request->file('payment_proof'));

        $summary = sprintf(
            'Sewa berhasil disimpan: Garasi %s, %s – %s, total %s.',
            $rental->garage->code,
            Format::date($rental->start_date),
            Format::date($rental->end_date),
            Format::rupiah($rental->totalFee()),
        );

        return redirect()->route('rentals.index')
            ->with('success', $summary)
            ->with('newRentalId', $rental->id);
    }

    /**
     * Describe each vehicle type's form rules for the form's script.
     *
     * @return array<string, array{garageTypes: list<string>, requiresPlateNumber: bool, isElectric: bool}>
     */
    private function vehicleRules(): array
    {
        return collect(VehicleType::cases())->mapWithKeys(fn (VehicleType $type) => [$type->value => [
            'garageTypes' => array_column($type->allowedGarageTypes(), 'value'),
            'requiresPlateNumber' => $type->requiresPlateNumber(),
            'isElectric' => $type->isElectric(),
        ]])->all();
    }
}
