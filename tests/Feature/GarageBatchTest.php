<?php

namespace Tests\Feature;

use App\Enums\GarageType;
use App\Models\Garage;
use App\Models\Rental;
use App\Models\User;
use App\Services\GarageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FT01–FT04: batch garage creation and the garage list (S05, S10).
 */
class GarageBatchTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    /** FT01 */
    public function test_first_bicycle_garages_are_numbered_from_s001(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/garages', [
            'type' => 'bicycle',
            'quantity' => 3,
            'daily_price' => 10_000,
        ]);

        $response->assertRedirect('/admin/garages?type=bicycle')
            ->assertSessionHas('success', '3 garasi berhasil dibuat: S001 s.d. S003.');
        $this->assertSame(['S001', 'S002', 'S003'], Garage::orderBy('code')->pluck('code')->all());
        $this->assertSame(0, Garage::whereNotNull('charging_price_per_kwh')->count());
    }

    /** FT02 */
    public function test_numbering_continues_from_existing_garages_of_the_same_type(): void
    {
        $service = new GarageService;
        $service->createBatch(GarageType::Car, 9, 50_000, null);
        $service->createBatch(GarageType::Bicycle, 4, 10_000, null);

        $created = $service->createBatch(GarageType::Car, 2, 60_000, null);

        $this->assertSame(['C010', 'C011'], $created->pluck('code')->all());
        $this->assertSame(60_000, $created->first()->daily_price);
    }

    /** FT03 */
    public function test_batch_that_exceeds_999_garages_of_a_type_is_rejected(): void
    {
        (new GarageService)->createBatch(GarageType::Car, 990, 50_000, null);

        $response = $this->actingAs($this->admin)->post('/admin/garages', [
            'type' => 'car',
            'quantity' => 10,
            'daily_price' => 50_000,
        ]);

        $response->assertSessionHasErrors(['quantity' => 'Jumlah melebihi batas. Sisa kode tersedia: 9.']);
        $this->assertSame(990, Garage::count());
    }

    /** FT04 */
    public function test_electric_car_garage_requires_a_charging_price(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/garages', [
            'type' => 'electric_car',
            'quantity' => 2,
            'daily_price' => 75_000,
        ]);

        $response->assertSessionHasErrors(['charging_price_per_kwh' => 'Harga pengisian daya per kWh wajib diisi.']);
        $this->assertSame(0, Garage::count());
    }

    /** FT04 */
    public function test_electric_car_garage_stores_its_charging_price(): void
    {
        $this->actingAs($this->admin)->post('/admin/garages', [
            'type' => 'electric_car',
            'quantity' => 1,
            'daily_price' => 75_000,
            'charging_price_per_kwh' => 2_500,
        ])->assertSessionHas('success', '1 garasi berhasil dibuat: E001.');

        $this->assertSame(2_500, Garage::firstWhere('code', 'E001')->charging_price_per_kwh);
    }

    public function test_charging_price_is_ignored_for_other_garage_types(): void
    {
        $this->actingAs($this->admin)->post('/admin/garages', [
            'type' => 'car',
            'quantity' => 1,
            'daily_price' => 50_000,
            'charging_price_per_kwh' => 2_500,
        ]);

        $this->assertNull(Garage::firstWhere('code', 'C001')->charging_price_per_kwh);
    }

    public function test_list_filters_by_type_and_shows_todays_status(): void
    {
        $rentedGarage = Garage::factory()->create(['code' => 'C001']);
        Rental::factory()->for($rentedGarage)->between('today', '+3 days')->create();
        Garage::factory()->create(['code' => 'C002']);
        Garage::factory()->bicycle()->create(['code' => 'S001']);

        $response = $this->actingAs($this->admin)->get('/admin/garages?type=car');

        $response->assertOk()
            ->assertSeeInOrder(['C001', 'Disewa', 'C002', 'Tersedia'])
            ->assertDontSee('S001')
            ->assertSee('Garasi Mobil (2)')
            ->assertSee('Garasi Sepeda (1)');
    }
}
