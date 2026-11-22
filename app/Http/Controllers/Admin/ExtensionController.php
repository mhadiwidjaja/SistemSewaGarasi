<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RentalStatus;
use App\Enums\RentalType;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExtendRentalRequest;
use App\Http\Requests\QuoteExtensionRequest;
use App\Models\Rental;
use App\Services\RentalCalculator;
use App\Services\RentalService;
use App\Support\Format;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Lets the admin extend a garage rental that is running today, after the renter paid the extra fee.
 */
class ExtensionController extends Controller
{
    /**
     * List the garages being rented today (S06).
     */
    public function index(): View
    {
        $rentals = Rental::activeOn(CarbonImmutable::today())
            ->with(['garage', 'renter'])
            ->get()
            ->sortBy('garage.code');

        return view('admin.extensions.index', ['rentals' => $rentals]);
    }

    /**
     * Show the extension form for one rental (S07).
     */
    public function edit(Rental $rental, RentalCalculator $calculator): View
    {
        $this->ensureActiveToday($rental);

        return view('admin.extensions.edit', [
            'rental' => $rental->load(['garage', 'renter']),
            'maxDailyDays' => $calculator->maxDailyDays($rental->extensionStartDate()),
        ]);
    }

    /**
     * Return the new end date and the fee still to be paid, as JSON for the form.
     */
    public function quote(QuoteExtensionRequest $request, Rental $rental, RentalService $rentalService): JsonResponse
    {
        $this->ensureActiveToday($rental);
        $extension = $rentalService->quoteExtension($rental, $request->rentalType(), $request->days());

        return response()->json([
            'max_days' => $request->maxDailyDays(),
            'end_date' => Format::date($extension['end_date']),
            'fee' => Format::rupiah($extension['fee']),
            'fee_breakdown' => $this->feeBreakdown($rental, $request->rentalType(), $request->days()),
        ]);
    }

    /**
     * Save the extension and return to today's list.
     */
    public function update(ExtendRentalRequest $request, Rental $rental, RentalService $rentalService): RedirectResponse
    {
        $this->ensureActiveToday($rental);
        $extension = $rentalService->extendRental(
            $rental,
            $request->rentalType(),
            $request->days(),
            $request->file('payment_proof'),
        );

        return redirect()->route('admin.extensions.index')->with('success', sprintf(
            'Sewa garasi %s diperpanjang sampai %s. Sisa biaya %s sudah dibayar.',
            $rental->garage->code,
            Format::date($extension->new_end_date),
            Format::rupiah($extension->fee),
        ));
    }

    /**
     * Only rentals running today can be extended.
     */
    private function ensureActiveToday(Rental $rental): void
    {
        abort_unless($rental->status() === RentalStatus::Active, Response::HTTP_NOT_FOUND);
    }

    /**
     * Explain how the extension fee is computed, e.g. "5 hari × Rp 50.000" or "25 × Rp 50.000".
     */
    private function feeBreakdown(Rental $rental, RentalType $type, ?int $days): string
    {
        $dailyPrice = Format::rupiah($rental->garage->daily_price);

        return $type === RentalType::Monthly
            ? RentalCalculator::MONTHLY_PRICE_MULTIPLIER." × {$dailyPrice}"
            : "{$days} hari × {$dailyPrice}";
    }
}
