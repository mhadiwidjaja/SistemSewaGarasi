<?php

namespace Tests\Feature;

use App\Enums\RegistrationStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FT05–FT07, FT26, FT34: renter registration and login.
 */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function validRegistration(): array
    {
        return [
            'nik' => '3578012345678901',
            'name' => 'Budi Santoso',
            'phone' => '0812-3456 7890',
            'email' => 'Budi@Example.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ];
    }

    /** FT05: Valid registration creates a pending renter. */
    public function test_valid_registration_creates_a_pending_renter(): void
    {
        $response = $this->post('/register', $this->validRegistration());

        $response->assertRedirect('/login')->assertSessionHas('success');
        $renter = User::firstWhere('nik', '3578012345678901');
        $this->assertSame(UserRole::Renter, $renter->role);
        $this->assertSame(RegistrationStatus::Pending, $renter->status);
        $this->assertSame('081234567890', $renter->phone);
        $this->assertSame('budi@example.com', $renter->email);
        $this->assertGuest();
    }

    /** FT06: Registration rejects a NIK that is not 16 digits. */
    public function test_registration_rejects_a_nik_that_is_not_16_digits(): void
    {
        $response = $this->post('/register', ['nik' => '35780123'] + $this->validRegistration());

        $response->assertSessionHasErrors(['nik' => 'NIK harus 16 digit angka.']);
        $this->assertDatabaseCount('users', 0);
    }

    /** FT06: Registration rejects a duplicate NIK or email. */
    public function test_registration_rejects_a_duplicate_nik_or_email(): void
    {
        User::factory()->create(['nik' => '3578012345678901', 'email' => 'budi@example.com']);

        $response = $this->post('/register', $this->validRegistration());

        $response->assertSessionHasErrors([
            'nik' => 'NIK sudah terdaftar.',
            'email' => 'Email sudah terdaftar.',
        ]);
    }

    /** FT06: Registration rejects a mismatched password confirmation. */
    public function test_registration_rejects_a_mismatched_password_confirmation(): void
    {
        $response = $this->post('/register', ['password_confirmation' => 'lainnya123'] + $this->validRegistration());

        $response->assertSessionHasErrors(['password' => 'Konfirmasi kata sandi tidak cocok.']);
    }

    /** FT26: Registration cannot choose its own role or status. */
    public function test_registration_cannot_choose_its_own_role_or_status(): void
    {
        $this->post('/register', ['role' => 'admin', 'status' => 'approved'] + $this->validRegistration());

        $user = User::firstWhere('nik', '3578012345678901');
        $this->assertSame(UserRole::Renter, $user->role);
        $this->assertSame(RegistrationStatus::Pending, $user->status);
    }

    /** FT07: Pending renter cannot log in. */
    public function test_pending_renter_cannot_log_in(): void
    {
        $renter = User::factory()->pending()->create();

        $response = $this->post('/login', ['email' => $renter->email, 'password' => 'password']);

        $response->assertSessionHasErrors(['email' => 'Akun Anda masih menunggu verifikasi admin.']);
        $this->assertGuest();
    }

    /** FT07: Rejected renter cannot log in. */
    public function test_rejected_renter_cannot_log_in(): void
    {
        $renter = User::factory()->rejected()->create();

        $response = $this->post('/login', ['email' => $renter->email, 'password' => 'password']);

        $response->assertSessionHasErrors(['email' => 'Pendaftaran Anda ditolak oleh admin.']);
        $this->assertGuest();
    }

    /** FT07: Wrong password is refused. */
    public function test_wrong_password_is_refused(): void
    {
        $renter = User::factory()->create();

        $response = $this->post('/login', ['email' => $renter->email, 'password' => 'salah']);

        $response->assertSessionHasErrors(['email' => 'Email atau kata sandi salah.']);
        $this->assertGuest();
    }

    /** FT34: Login is blocked for a minute after five attempts. */
    public function test_login_is_blocked_for_a_minute_after_five_attempts(): void
    {
        $renter = User::factory()->create();

        foreach (range(1, 5) as $attempt) {
            $this->post('/login', ['email' => $renter->email, 'password' => 'salah']);
        }

        $this->post('/login', ['email' => $renter->email, 'password' => 'password'])->assertTooManyRequests();
        $this->assertGuest();
    }

    /** Approved users land on their role home page. */
    public function test_approved_users_land_on_their_role_home_page(): void
    {
        $renter = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->post('/login', ['email' => $renter->email, 'password' => 'password'])->assertRedirect('/');
        $this->get('/')->assertRedirect('/rentals');
        $this->post('/logout')->assertRedirect('/login');

        $this->post('/login', ['email' => $admin->email, 'password' => 'password']);
        $this->get('/')->assertRedirect('/admin/registrations');
    }
}
