<?php

namespace Tests\Feature;

use App\Enums\RentalType;
use App\Models\Garage;
use App\Models\Rental;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * FT19–FT22, FT29: the admin extends a rental that runs today (S06, S07).
 * An extension starts the day after the current end date and follows the rental rules.
 */
class ExtensionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Garage $garage;

    /** Prepare the data shared by the tests in this class. */
    protected function setUp(): void
    {
        parent::setUp();
        CarbonImmutable::setTestNow('2026-01-21 10:00:00');
        Storage::fake('local');

        $this->admin = User::factory()->admin()->create();
        $this->garage = Garage::factory()->create(['code' => 'C001', 'daily_price' => 50_000]);
    }

    /** Release the fixed test date. */
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    /**
     * Create a rental of the test garage for the given period.
     */
    private function rentalBetween(string $start, string $end): Rental
    {
        return Rental::factory()->for($this->garage)->between($start, $end)->create();
    }

    /**
     * Submit the extension form for a rental.
     *
     * @param  array<string, mixed>  $fields
     */
    private function extend(Rental $rental, array $fields): TestResponse
    {
        return $this->actingAs($this->admin)->put("/admin/extensions/{$rental->id}", $fields + [
            'payment_proof' => new UploadedFile(base_path('tests/Fixtures/proof.png'), 'proof.png', 'image/png', null, true),
        ]);
    }

    /** FT19: List shows only rentals running today. */
    public function test_list_shows_only_rentals_running_today(): void
    {
        $this->rentalBetween('2026-01-21', '2026-01-30');
        Rental::factory()->for(Garage::factory()->create(['code' => 'C002']))->between('2026-01-01', '2026-01-20')->create();
        Rental::factory()->for(Garage::factory()->create(['code' => 'C003']))->between('2026-01-22', '2026-01-25')->create();

        $response = $this->actingAs($this->admin)->get('/admin/extensions');

        $response->assertOk()->assertSee('C001')->assertDontSee('C002')->assertDontSee('C003');
    }

    /** FT20: Daily extension is limited by the month it starts in. */
    public function test_daily_extension_is_limited_by_the_month_it_starts_in(): void
    {
        $rental = $this->rentalBetween('2026-01-21', '2026-01-31');

        $response = $this->extend($rental, ['rental_type' => 'daily', 'days' => 28]);

        $response->assertSessionHasErrors(['days' => 'Jumlah hari maksimal 27.']);
        $this->assertSame('2026-01-31', $rental->fresh()->end_date->toDateString());
    }

    /** FT21: Daily extension adds days from the day after the current end. */
    public function test_daily_extension_adds_days_from_the_day_after_the_current_end(): void
    {
        $rental = $this->rentalBetween('2026-01-21', '2026-01-30');

        $this->extend($rental, ['rental_type' => 'daily', 'days' => 5])
            ->assertRedirect('/admin/extensions')
            ->assertSessionHas('success', 'Sewa garasi C001 diperpanjang sampai 04/02/2026. Sisa biaya Rp 250.000 sudah dibayar.');

        $extension = $rental->extensions()->sole();
        $this->assertSame(RentalType::Daily, $extension->rental_type);
        $this->assertSame('2026-01-30', $extension->previous_end_date->toDateString());
        $this->assertSame(250_000, $extension->fee);
        Storage::disk('local')->assertExists($extension->payment_proof_path);
        $this->assertSame('2026-02-04', $rental->fresh()->end_date->toDateString());
    }

    /** FT21: Monthly extension lasts the days of its start month and costs 25 days. */
    public function test_monthly_extension_lasts_the_days_of_its_start_month_and_costs_25_days(): void
    {
        $rental = $this->rentalBetween('2026-01-21', '2026-01-30');

        $this->extend($rental, ['rental_type' => 'monthly']);

        $extension = $rental->extensions()->sole();
        $this->assertSame('2026-03-02', $extension->new_end_date->toDateString());
        $this->assertSame(1_250_000, $extension->fee);
    }

    /** FT22: Extension is refused when the garage is booked in the added days. */
    public function test_extension_is_refused_when_the_garage_is_booked_in_the_added_days(): void
    {
        $rental = $this->rentalBetween('2026-01-21', '2026-01-30');
        $this->rentalBetween('2026-02-02', '2026-02-10');

        $response = $this->extend($rental, ['rental_type' => 'daily', 'days' => 5]);

        $response->assertSessionHasErrors(['end_date' => 'Garasi C001 sudah dipesan pada periode tersebut.']);
        $this->assertSame(0, $rental->extensions()->count());
    }

    /** Form shows the rental and when the extension starts. */
    public function test_form_shows_the_rental_and_when_the_extension_starts(): void
    {
        $rental = $this->rentalBetween('2026-01-21', '2026-01-30');

        $response = $this->actingAs($this->admin)->get("/admin/extensions/{$rental->id}/edit");

        $response->assertOk()
            ->assertSeeInOrder(['Toyota Avanza', 'Mobil · B 1234 XYZ', '21/01/2026 – 30/01/2026'])
            ->assertSeeInOrder(['Mulai Perpanjangan', '31/01/2026', 'Harian', 'Bulanan', 'Maksimal 30 hari.']);
    }

    /** FT29: Quote warns when the garage is booked in the added days. */
    public function test_quote_warns_when_the_garage_is_booked_in_the_added_days(): void
    {
        $rental = $this->rentalBetween('2026-01-21', '2026-01-30');
        $this->rentalBetween('2026-02-02', '2026-02-10');

        $response = $this->actingAs($this->admin)->getJson("/admin/extensions/{$rental->id}/quote?rental_type=daily&days=5");

        $response->assertUnprocessable()->assertJson(['message' => 'Garasi C001 sudah dipesan pada periode tersebut.']);
    }

    /** Rental not running today cannot be extended. */
    public function test_rental_not_running_today_cannot_be_extended(): void
    {
        $upcoming = $this->rentalBetween('2026-01-25', '2026-01-30');

        $this->actingAs($this->admin)->get("/admin/extensions/{$upcoming->id}/edit")->assertNotFound();
        $this->extend($upcoming, ['rental_type' => 'daily', 'days' => 1])->assertNotFound();
    }

    /** Quote shows new end date and remaining fee. */
    public function test_quote_shows_new_end_date_and_remaining_fee(): void
    {
        $rental = $this->rentalBetween('2026-01-21', '2026-01-30');

        $response = $this->actingAs($this->admin)->getJson("/admin/extensions/{$rental->id}/quote?rental_type=daily&days=5");

        $response->assertOk()->assertExactJson([
            'max_days' => 30,
            'end_date' => '04/02/2026',
            'fee' => 'Rp 250.000',
            'fee_breakdown' => '5 hari × Rp 50.000',
        ]);
    }

    /** Extension counts in the renter's total. */
    public function test_extension_counts_in_the_renters_total(): void
    {
        $rental = $this->rentalBetween('2026-01-21', '2026-01-30');
        $this->extend($rental, ['rental_type' => 'daily', 'days' => 5]);

        $this->assertSame(150_000 + 250_000, $rental->fresh()->totalFee());
    }
}
