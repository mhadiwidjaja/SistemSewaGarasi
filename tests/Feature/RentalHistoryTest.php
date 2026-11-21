<?php

namespace Tests\Feature;

use App\Models\Rental;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FT18: the renter's rental history (S09).
 */
class RentalHistoryTest extends TestCase
{
    use RefreshDatabase;

    /** FT18 */
    public function test_renter_sees_only_their_own_rentals(): void
    {
        $renter = User::factory()->create();
        $ownRental = Rental::factory()->for($renter, 'renter')->create();
        $otherRental = Rental::factory()->create();

        $response = $this->actingAs($renter)->get('/rentals');

        $response->assertOk()
            ->assertSee($ownRental->garage->code)
            ->assertDontSee($otherRental->garage->code);
    }

    /** FT18 */
    public function test_total_includes_charging_and_extension_fees(): void
    {
        $renter = User::factory()->create();
        $rental = Rental::factory()->for($renter, 'renter')->create([
            'rental_fee' => 150_000,
            'charging_fee' => 112_500,
        ]);
        $rental->extensions()->create([
            'rental_type' => 'daily',
            'previous_end_date' => $rental->end_date,
            'new_end_date' => $rental->end_date->addDays(5),
            'fee' => 250_000,
            'payment_proof_path' => 'payment-proofs/extension.png',
        ]);

        $response = $this->actingAs($renter)->get('/rentals');

        $response->assertSee('Rp 512.500')->assertSee('Diperpanjang');
    }

    public function test_empty_history_shows_a_message(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/rentals')
            ->assertSee('Anda belum memiliki riwayat sewa.');
    }
}
