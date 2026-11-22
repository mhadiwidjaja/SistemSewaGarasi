<?php

namespace App\Services;

use App\Enums\GarageType;
use App\Models\Rental;
use App\Support\Format;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Garage rental report: number of rentals and revenue, grouped per month, day, garage type or renter.
 *
 * Revenue of a rental = rental fee + charging fee + all its extension fees, counted on the rental's start date.
 * Grouping is done in PHP so the same code works on MySQL and on the SQLite test database.
 *
 * Every row has the shape: array{label: string, detail: ?string, rental_count: int, revenue: int}.
 */
class ReportService
{
    /**
     * One row for each of the 12 months of a year, including months without rentals.
     *
     * @return Collection<int, array{label: string, detail: ?string, rental_count: int, revenue: int}>
     */
    public function perMonth(int $year): Collection
    {
        $firstDay = CarbonImmutable::create($year);
        $rentalsByMonth = $this->rentalsStartingBetween($firstDay, $firstDay->endOfYear())
            ->groupBy(fn (Rental $rental) => $rental->start_date->month);

        return collect(range(1, 12))->map(fn (int $month) => $this->row(
            $firstDay->month($month)->locale('id')->translatedFormat('F'),
            $rentalsByMonth->get($month, collect()),
        ));
    }

    /**
     * One row for each day of a month, including days without rentals.
     *
     * @return Collection<int, array{label: string, detail: ?string, rental_count: int, revenue: int}>
     */
    public function perDay(int $year, int $month): Collection
    {
        $firstDay = CarbonImmutable::create($year, $month);
        $rentalsByDay = $this->rentalsStartingBetween($firstDay, $firstDay->endOfMonth())
            ->groupBy(fn (Rental $rental) => $rental->start_date->day);

        return collect(range(1, $firstDay->daysInMonth))->map(fn (int $day) => $this->row(
            Format::date($firstDay->day($day)),
            $rentalsByDay->get($day, collect()),
        ));
    }

    /**
     * One row for each garage type, over all time.
     *
     * @return Collection<int, array{label: string, detail: ?string, rental_count: int, revenue: int}>
     */
    public function perGarageType(): Collection
    {
        $rentalsByType = Rental::with(['garage', 'extensions'])->get()
            ->groupBy(fn (Rental $rental) => $rental->garage->type->value);

        return collect(GarageType::cases())->map(fn (GarageType $type) => $this->row(
            $type->label(),
            $rentalsByType->get($type->value, collect()),
        ));
    }

    /**
     * One row for each renter with at least one rental, over all time, highest revenue first.
     *
     * @return Collection<int, array{label: string, detail: ?string, rental_count: int, revenue: int}>
     */
    public function perRenter(): Collection
    {
        return Rental::with(['renter', 'extensions'])->get()
            ->groupBy('renter_id')
            ->map(fn (Collection $rentals) => $this->row(
                $rentals->first()->renter->name,
                $rentals,
                'NIK '.$rentals->first()->renter->nik,
            ))
            ->sortByDesc('revenue')
            ->values();
    }

    /**
     * Get the rentals starting within the period, with their extensions.
     *
     * @return Collection<int, Rental>
     */
    private function rentalsStartingBetween(CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        return Rental::with('extensions')
            ->whereDate('start_date', '>=', $start)
            ->whereDate('start_date', '<=', $end)
            ->get();
    }

    /**
     * Summarize a group of rentals as a report row.
     *
     * @param  Collection<int, Rental>  $rentals
     * @return array{label: string, detail: ?string, rental_count: int, revenue: int}
     */
    private function row(string $label, Collection $rentals, ?string $detail = null): array
    {
        return [
            'label' => $label,
            'detail' => $detail,
            'rental_count' => $rentals->count(),
            'revenue' => $rentals->sum(fn (Rental $rental) => $rental->totalFee()),
        ];
    }
}
