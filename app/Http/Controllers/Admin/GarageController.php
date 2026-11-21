<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GarageType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGarageBatchRequest;
use App\Models\Garage;
use App\Services\GarageService;
use App\Services\RentalCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Admin garage management: the garage list and batch creation.
 */
class GarageController extends Controller
{
    private const GARAGES_PER_PAGE = 25;

    /**
     * List garages with today's status, optionally filtered by type (S10).
     */
    public function index(Request $request): View
    {
        $selectedType = GarageType::tryFrom((string) $request->query('type'));

        $garages = Garage::with('currentRental')
            ->when($selectedType, fn ($query) => $query->where('type', $selectedType))
            ->orderBy('code')
            ->paginate(self::GARAGES_PER_PAGE)
            ->withQueryString();

        return view('admin.garages.index', [
            'garages' => $garages,
            'selectedType' => $selectedType,
            'countsByType' => $this->countsByType(),
        ]);
    }

    /**
     * Show the batch creation form (S05).
     */
    public function create(GarageService $garageService): View
    {
        return view('admin.garages.create', [
            'types' => GarageType::cases(),
            'nextNumbers' => $this->perType(fn (GarageType $type) => $garageService->nextNumber($type)),
            'remainingCodes' => $this->perType(fn (GarageType $type) => $garageService->remainingCodes($type)),
            'codePrefixes' => $this->perType(fn (GarageType $type) => $type->codePrefix()),
            'codeDigits' => GarageService::CODE_DIGITS,
            'monthlyMultiplier' => RentalCalculator::MONTHLY_PRICE_MULTIPLIER,
        ]);
    }

    /**
     * Create the batch and return to the list of that type.
     */
    public function store(StoreGarageBatchRequest $request, GarageService $garageService): RedirectResponse
    {
        $type = $request->garageType();
        $chargingPrice = $request->validated('charging_price_per_kwh');
        $garages = $garageService->createBatch(
            $type,
            $request->integer('quantity'),
            $request->integer('daily_price'),
            $chargingPrice === null ? null : (int) $chargingPrice,
        );

        $codes = $garages->count() === 1
            ? $garages->first()->code
            : "{$garages->first()->code} s.d. {$garages->last()->code}";

        return redirect()->route('admin.garages.index', ['type' => $type->value])
            ->with('success', "{$garages->count()} garasi berhasil dibuat: {$codes}.");
    }

    /**
     * Count garages per type, for the filter tabs.
     *
     * @return Collection<string, int> Keyed by garage type value.
     */
    private function countsByType(): Collection
    {
        return Garage::selectRaw('type, count(*) as total')->groupBy('type')->pluck('total', 'type');
    }

    /**
     * Compute a value for every garage type.
     *
     * @return array<string, mixed> Keyed by garage type value.
     */
    private function perType(callable $valueOf): array
    {
        return collect(GarageType::cases())
            ->mapWithKeys(fn (GarageType $type) => [$type->value => $valueOf($type)])
            ->all();
    }
}
