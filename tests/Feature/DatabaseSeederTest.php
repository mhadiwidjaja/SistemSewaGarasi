<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FT33: the admin account created by the seeder.
 */
class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    /** FT33: Seeding twice keeps one admin who can log in. */
    public function test_seeding_twice_keeps_one_admin_who_can_log_in(): void
    {
        $this->seed();
        $this->seed();

        $this->assertSame(1, User::where('email', 'admin@sewagarasi.test')->count());
        $this->post('/login', ['email' => 'admin@sewagarasi.test', 'password' => 'admin123'])->assertRedirect('/');
        $this->assertTrue(auth()->user()->isAdmin());
    }
}
