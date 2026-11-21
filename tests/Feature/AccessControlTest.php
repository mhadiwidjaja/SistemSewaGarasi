<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FT09: pages are only reachable by the right role.
 */
class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    /** FT09 */
    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/rentals')->assertRedirect('/login');
        $this->get('/admin/registrations')->assertRedirect('/login');
    }

    /** FT09 */
    public function test_renter_cannot_open_admin_pages(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/registrations')->assertForbidden();
    }

    /** FT09 */
    public function test_admin_cannot_open_renter_pages(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get('/rentals')->assertForbidden();
    }
}
