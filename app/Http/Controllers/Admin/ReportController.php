<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReportType;
use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * The garage rental report (S08): per month in a year, per day in a month, per garage type or per renter.
 */
class ReportController extends Controller
{
    /**
     * Show the selected report tab with its filter and results.
     */
    public function index(Request $request, ReportService $reportService): View
    {
        $filters = $request->validate([
            'type' => ['nullable', 'string'],
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            // A regex instead of date_format:Y-m, which wrongly rejects short months on the 29th–31st.
            'month' => ['nullable', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
        ]);

        $reportType = ReportType::tryFrom($filters['type'] ?? '') ?? ReportType::PerMonth;
        $year = (int) ($filters['year'] ?? CarbonImmutable::today()->year);
        $month = CarbonImmutable::createFromFormat('!Y-m', $filters['month'] ?? CarbonImmutable::today()->format('Y-m'));

        return view('admin.reports.index', [
            'reportType' => $reportType,
            'year' => $year,
            'month' => $month,
            'rows' => $this->rows($reportService, $reportType, $year, $month),
        ]);
    }

    /**
     * Build the rows of the selected report.
     *
     * @return Collection<int, array{label: string, detail: ?string, rental_count: int, revenue: int}>
     */
    private function rows(ReportService $reportService, ReportType $type, int $year, CarbonImmutable $month): Collection
    {
        return match ($type) {
            ReportType::PerMonth => $reportService->perMonth($year),
            ReportType::PerDay => $reportService->perDay($month->year, $month->month),
            ReportType::PerGarageType => $reportService->perGarageType(),
            ReportType::PerRenter => $reportService->perRenter(),
        };
    }
}
