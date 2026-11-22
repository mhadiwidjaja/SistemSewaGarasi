<?php

namespace Tests\Feature;

use App\Enums\RegistrationStatus;
use App\Models\Rental;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FT09, FT27: pages and actions are only reachable by the right role.
 */
class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    /** FT09: Guest is redirected to login. */
    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/rentals')->assertRedirect('/login');
        $this->get('/admin/registrations')->assertRedirect('/login');
    }

    /** FT09: Renter cannot open admin pages. */
    public function test_renter_cannot_open_admin_pages(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/registrations')->assertForbidden();
    }

    /** FT27: Renter cannot perform admin actions. */
    public function test_renter_cannot_perform_admin_actions(): void
    {
        $renter = User::factory()->create();
        $pendingRenter = User::factory()->pending()->create();
        $rental = Rental::factory()->create();

        $this->actingAs($renter)->post("/admin/registrations/{$pendingRenter->id}/approve")->assertForbidden();
        $this->actingAs($renter)->post('/admin/garages', ['type' => 'car', 'quantity' => 1, 'daily_price' => 1])->assertForbidden();
        $this->actingAs($renter)->put("/admin/extensions/{$rental->id}", ['rental_type' => 'monthly'])->assertForbidden();

        $this->assertSame(RegistrationStatus::Pending, $pendingRenter->fresh()->status);
        $this->assertSame(0, $rental->extensions()->count());
    }

    /** FT09: Admin cannot open renter pages. */
    public function test_admin_cannot_open_renter_pages(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get('/rentals')->assertForbidden();
    }
}
