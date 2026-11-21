<?php

namespace Database\Seeders;

use App\Enums\RegistrationStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Creates the single admin account. All other data is entered through the application.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Administrator',
            'email' => 'admin@sewagarasi.test',
            'password' => 'admin123',
            'role' => UserRole::Admin,
            'status' => RegistrationStatus::Approved,
        ]);
    }
}
