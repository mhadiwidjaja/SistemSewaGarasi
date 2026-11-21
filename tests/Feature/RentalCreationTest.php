<?php

namespace Tests\Feature;

use App\Enums\GarageType;
use App\Models\Garage;
use App\Models\Rental;
use App\Models\User;
use App\Services\RentalService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * FT10–FT17: availability and new rentals (S03). Today is fixed at 21/11/2026.
 */
class RentalCreationTest extends TestCase
{
    use RefreshDatabase;

    private User $renter;

    private Garage $carGarage;

    protected function setUp(): void
    {
        parent::setUp();
        CarbonImmutable::setTestNow('2026-11-21 10:00:00');
        Storage::fake('local');

        $this->renter = User::factory()->create();
        $this->carGarage = Garage::factory()->create(['code' => 'C001', 'daily_price' => 50_000]);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    /**
     * A valid daily car rental of 3 days starting today; override any field.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function rentalForm(array $overrides = []): array
    {
        return $overrides + [
            'vehicle_type' => 'car',
            'plate_number' => 'b  1234   xyz',
            'vehicle_brand_type' => 'Toyota Avanza',
            'rental_type' => 'daily',
            'days' => 3,
            'start_date' => '2026-11-21',
            'garage_type' => 'car',
            'garage_id' => $this->carGarage->id,
            'payment_proof' => new UploadedFile(base_path('tests/Fixtures/proof.png'), 'proof.png', 'image/png', null, true),
        ];
    }

    /**
     * A valid electric car rental (60 kWh, 15 kWh left) in the given garage.
     *
     * @return array<string, mixed>
     */
    private function electricCarForm(Garage $garage): array
    {
        return $this->rentalForm([
            'vehicle_type' => 'electric_car',
            'battery_capacity_kwh' => 60,
            'battery_left_kwh' => 15,
            'garage_type' => $garage->type->value,
            'garage_id' => $garage->id,
        ]);
    }

    public function test_form_opens_with_today_and_its_daily_limit(): void
    {
        $response = $this->actingAs($this->renter)->get('/rentals/create');

        $response->assertOk()->assertSee('value="2026-11-21"', false)->assertSee('Maksimal 29 hari.');
    }

    /** FT10 */
    public function test_garage_is_unavailable_only_when_periods_overlap(): void
    {
        Rental::factory()->for($this->carGarage)->between('2026-11-21', '2026-11-23')->create();
        $service = app(RentalService::class);

        $overlapping = $service->availableGarages(GarageType::Car, CarbonImmutable::parse('2026-11-23'), CarbonImmutable::parse('2026-11-25'));
        $afterwards = $service->availableGarages(GarageType::Car, CarbonImmutable::parse('2026-11-24'), CarbonImmutable::parse('2026-11-26'));

        $this->assertNotContains('C001', $overlapping->pluck('code'));
        $this->assertContains('C001', $afterwards->pluck('code'));
    }

    /** FT11 */
    public function test_motorcycle_cannot_choose_a_car_garage_type(): void
    {
        $response = $this->actingAs($this->renter)->post('/rentals', $this->rentalForm(['vehicle_type' => 'motorcycle']));

        $response->assertSessionHasErrors(['garage_type' => 'Jenis garasi tidak sesuai dengan jenis kendaraan.']);
        $this->assertDatabaseCount('rentals', 0);
    }

    /** FT11 */
    public function test_motorcycle_cannot_be_placed_in_a_car_garage(): void
    {
        $response = $this->actingAs($this->renter)->post('/rentals', $this->rentalForm([
            'vehicle_type' => 'motorcycle',
            'garage_type' => 'bicycle',
        ]));

        $response->assertSessionHasErrors(['garage_id' => 'Garasi Mobil tidak dapat digunakan untuk Motor.']);
        $this->assertDatabaseCount('rentals', 0);
    }

    /** FT12 */
    public function test_electric_car_in_electric_garage_pays_for_charging_to_full(): void
    {
        $electricGarage = Garage::factory()->electricCar()->create(['daily_price' => 75_000]);

        $this->actingAs($this->renter)->post('/rentals', $this->electricCarForm($electricGarage));

        $rental = Rental::sole();
        $this->assertSame(225_000, $rental->rental_fee);
        $this->assertSame(112_500, $rental->charging_fee);
    }

    /** FT12 */
    public function test_electric_car_in_car_garage_pays_no_charging_fee(): void
    {
        $this->actingAs($this->renter)->post('/rentals', $this->electricCarForm($this->carGarage));

        $this->assertSame(0, Rental::sole()->charging_fee);
    }

    /** FT13 */
    public function test_start_date_cannot_be_in_the_past(): void
    {
        $response = $this->actingAs($this->renter)->post('/rentals', $this->rentalForm(['start_date' => '2026-11-20']));

        $response->assertSessionHasErrors(['start_date' => 'Tanggal mulai tidak boleh sebelum hari ini.']);
    }

    /** FT14 */
    public function test_daily_rental_may_not_reach_the_length_of_the_start_month(): void
    {
        $response = $this->actingAs($this->renter)->post('/rentals', $this->rentalForm([
            'start_date' => '2027-02-01',
            'days' => 28,
        ]));

        $response->assertSessionHasErrors(['days' => 'Jumlah hari maksimal 27.']);
    }

    /** FT15 */
    public function test_bicycle_needs_no_plate_number(): void
    {
        $bicycleGarage = Garage::factory()->bicycle()->create();

        $response = $this->actingAs($this->renter)->post('/rentals', $this->rentalForm([
            'vehicle_type' => 'bicycle',
            'plate_number' => '',
            'garage_type' => 'bicycle',
            'garage_id' => $bicycleGarage->id,
        ]));

        $response->assertSessionHasNoErrors();
        $this->assertNull(Rental::sole()->plate_number);
    }

    /** FT15 */
    public function test_car_needs_a_plate_number(): void
    {
        $response = $this->actingAs($this->renter)->post('/rentals', $this->rentalForm(['plate_number' => '']));

        $response->assertSessionHasErrors(['plate_number' => 'Nomor polisi wajib diisi.']);
    }

    /** FT16 */
    public function test_battery_left_cannot_exceed_capacity(): void
    {
        $form = ['battery_left_kwh' => 61] + $this->electricCarForm($this->carGarage);

        $response = $this->actingAs($this->renter)->post('/rentals', $form);

        $response->assertSessionHasErrors(['battery_left_kwh' => 'Sisa baterai tidak boleh melebihi kapasitas baterai.']);
    }

    /** FT17 */
    public function test_valid_rental_is_saved_and_the_garage_becomes_used(): void
    {
        $response = $this->actingAs($this->renter)->post('/rentals', $this->rentalForm());

        $response->assertRedirect('/rentals')
            ->assertSessionHas('success', 'Sewa berhasil disimpan: Garasi C001, 21/11/2026 – 23/11/2026, total Rp 150.000.');

        $rental = Rental::sole();
        $this->assertTrue($rental->renter->is($this->renter));
        $this->assertSame('B 1234 XYZ', $rental->plate_number);
        $this->assertSame('2026-11-23', $rental->end_date->toDateString());
        $this->assertSame(150_000, $rental->rental_fee);
        Storage::disk('local')->assertExists($rental->payment_proof_path);
        $this->assertFalse($this->carGarage->isAvailableBetween(CarbonImmutable::today(), CarbonImmutable::today()));
    }

    public function test_monthly_rental_costs_25_days_and_lasts_the_start_month(): void
    {
        $this->actingAs($this->renter)->post('/rentals', $this->rentalForm([
            'rental_type' => 'monthly',
            'days' => 99,
            'start_date' => '2026-12-05',
        ]));

        $rental = Rental::sole();
        $this->assertSame('2027-01-04', $rental->end_date->toDateString());
        $this->assertSame(1_250_000, $rental->rental_fee);
    }

    public function test_booked_garage_cannot_be_rented_again(): void
    {
        Rental::factory()->for($this->carGarage)->between('2026-11-22', '2026-11-30')->create();

        $response = $this->actingAs($this->renter)->post('/rentals', $this->rentalForm());

        $response->assertSessionHasErrors(['garage_id' => 'Garasi C001 sudah tidak tersedia, silakan pilih garasi lain.']);
    }

    public function test_quote_lists_available_garages_with_their_total(): void
    {
        $query = http_build_query([
            'vehicle_type' => 'car',
            'rental_type' => 'daily',
            'days' => 3,
            'start_date' => '2026-11-21',
            'garage_type' => 'car',
        ]);

        $response = $this->actingAs($this->renter)->getJson("/rentals/quote?{$query}");

        $response->assertOk()->assertJson([
            'max_days' => 29,
            'end_date' => '23/11/2026',
            'garages' => [['code' => 'C001', 'total_fee' => 'Rp 150.000', 'charging_fee' => null]],
        ]);
    }

    public function test_quote_explains_an_invalid_input_and_still_gives_the_daily_limit(): void
    {
        $query = http_build_query([
            'vehicle_type' => 'car',
            'rental_type' => 'daily',
            'days' => 30,
            'start_date' => '2026-11-21',
            'garage_type' => 'car',
        ]);

        $response = $this->actingAs($this->renter)->getJson("/rentals/quote?{$query}");

        $response->assertUnprocessable()->assertExactJson([
            'message' => 'Jumlah hari maksimal 29.',
            'max_days' => 29,
        ]);
    }
}
