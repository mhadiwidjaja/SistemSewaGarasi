<?php

namespace Tests\Feature;

use App\Enums\RegistrationStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FT08: the admin accepts or rejects new renter registrations.
 */
class RegistrationVerificationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_list_shows_only_pending_renters(): void
    {
        $pending = User::factory()->pending()->create();
        $approved = User::factory()->create();

        $response = $this->actingAs($this->admin)->get('/admin/registrations');

        $response->assertOk()->assertSee($pending->nik)->assertDontSee($approved->nik);
    }

    /** FT08 */
    public function test_approved_renter_can_log_in(): void
    {
        $renter = User::factory()->pending()->create();

        $this->actingAs($this->admin)
            ->post("/admin/registrations/{$renter->id}/approve")
            ->assertSessionHas('success', "Pendaftaran {$renter->name} diterima.");
        $this->assertSame(RegistrationStatus::Approved, $renter->fresh()->status);

        $this->post('/logout');
        $this->post('/login', ['email' => $renter->email, 'password' => 'password']);
        $this->assertAuthenticatedAs($renter);
    }

    /** FT08 */
    public function test_rejected_renter_is_marked_rejected(): void
    {
        $renter = User::factory()->pending()->create();

        $this->actingAs($this->admin)
            ->post("/admin/registrations/{$renter->id}/reject")
            ->assertSessionHas('success', "Pendaftaran {$renter->name} ditolak.");

        $this->assertSame(RegistrationStatus::Rejected, $renter->fresh()->status);
    }

    public function test_a_renter_already_decided_cannot_be_decided_again(): void
    {
        $renter = User::factory()->rejected()->create();

        $this->actingAs($this->admin)->post("/admin/registrations/{$renter->id}/approve")->assertNotFound();

        $this->assertSame(RegistrationStatus::Rejected, $renter->fresh()->status);
    }
}
