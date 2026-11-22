<?php

namespace Tests\Feature;

use App\Models\Garage;
use App\Models\Rental;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FT23–FT24: the garage rental report (S08).
 *
 * Data: Budi rents twice in January 2026 (one rental extended), Siti rents an electric garage in March 2026,
 * and one rental from 2025 must not appear in the 2026 reports.
 */
class ReportTest extends TestCase
{
    use RefreshDatabase;

    private ReportService $reports;

    /** Prepare the data shared by the tests in this class. */
    protected function setUp(): void
    {
        parent::setUp();
        $this->reports = new ReportService;

        $budi = User::factory()->create(['name' => 'Budi', 'nik' => '1111111111111111']);
        $siti = User::factory()->create(['name' => 'Siti', 'nik' => '2222222222222222']);
        $carGarage = Garage::factory()->create();
        $electricGarage = Garage::factory()->electricCar()->create();

        Rental::factory()->for($budi, 'renter')->for($carGarage)->between('2026-01-05', '2026-01-07')
            ->create(['rental_fee' => 150_000]);
        $extended = Rental::factory()->for($budi, 'renter')->for($carGarage)->between('2026-01-20', '2026-01-21')
            ->create(['rental_fee' => 100_000]);
        $extended->extensions()->create([
            'rental_type' => 'daily',
            'previous_end_date' => '2026-01-21',
            'new_end_date' => '2026-01-22',
            'fee' => 50_000,
            'payment_proof_path' => 'payment-proofs/extension.png',
        ]);
        Rental::factory()->for($siti, 'renter')->for($electricGarage)->between('2026-03-10', '2026-03-13')
            ->create(['rental_fee' => 200_000, 'charging_fee' => 20_000]);
        Rental::factory()->for($siti, 'renter')->for($carGarage)->between('2025-12-30', '2026-01-02')
            ->create(['rental_fee' => 999_000]);
    }

    /** FT23: Per month lists all 12 months with count and revenue. */
    public function test_per_month_lists_all_12_months_with_count_and_revenue(): void
    {
        $rows = $this->reports->perMonth(2026);

        $this->assertCount(12, $rows);
        $this->assertSame(['label' => 'Januari', 'detail' => null, 'rental_count' => 2, 'revenue' => 300_000], $rows[0]);
        $this->assertSame(0, $rows[1]['rental_count']);
        $this->assertSame(['label' => 'Maret', 'detail' => null, 'rental_count' => 1, 'revenue' => 220_000], $rows[2]);
        $this->assertSame(520_000, $rows->sum('revenue'));
    }

    /** FT24: Per day lists every day of the month. */
    public function test_per_day_lists_every_day_of_the_month(): void
    {
        $rows = $this->reports->perDay(2026, 1);

        $this->assertCount(31, $rows);
        $this->assertSame(['label' => '05/01/2026', 'detail' => null, 'rental_count' => 1, 'revenue' => 150_000], $rows[4]);
        $this->assertSame(150_000, $rows[19]['revenue']);
        $this->assertSame(2, $rows->sum('rental_count'));
    }

    /** FT24: Per garage type covers all time. */
    public function test_per_garage_type_covers_all_time(): void
    {
        $rows = $this->reports->perGarageType()->keyBy('label');

        $this->assertSame(0, $rows['Garasi Sepeda']['rental_count']);
        $this->assertSame(3, $rows['Garasi Mobil']['rental_count']);
        $this->assertSame(150_000 + 100_000 + 50_000 + 999_000, $rows['Garasi Mobil']['revenue']);
        $this->assertSame(220_000, $rows['Garasi Mobil Listrik']['revenue']);
    }

    /** FT24: Per renter is sorted by revenue. */
    public function test_per_renter_is_sorted_by_revenue(): void
    {
        $rows = $this->reports->perRenter();

        $this->assertSame(['Siti', 'Budi'], $rows->pluck('label')->all());
        $this->assertSame(['label' => 'Budi', 'detail' => 'NIK 1111111111111111', 'rental_count' => 2, 'revenue' => 300_000], $rows[1]);
    }

    /** Report page shows the selected tab with a total. */
    public function test_report_page_shows_the_selected_tab_with_a_total(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/admin/reports?type=per-bulan&year=2026');

        $response->assertOk()
            ->assertSeeInOrder(['Januari', '2', 'Rp 300.000', 'Total', '3', 'Rp 520.000']);
    }

    /** Month filter is accepted at the end of a long month. */
    public function test_month_filter_is_accepted_at_the_end_of_a_long_month(): void
    {
        $this->travelTo('2026-01-31');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin/reports?type=per-hari&month=2026-02')
            ->assertOk()
            ->assertSee('28/02/2026')
            ->assertDontSee('01/03/2026');
    }
}
