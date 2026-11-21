<?php

namespace Database\Factories;

use App\Enums\RegistrationStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * Builds users for tests. Defaults to an approved renter.
 *
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /** Hashed once and reused, because hashing is slow. */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nik' => fake()->unique()->numerify(str_repeat('#', 16)),
            'name' => fake()->name(),
            'phone' => fake()->numerify('08##########'),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => UserRole::Renter,
            'status' => RegistrationStatus::Approved,
        ];
    }

    /**
     * The admin account (no NIK or phone).
     */
    public function admin(): static
    {
        return $this->state(fn () => [
            'nik' => null,
            'phone' => null,
            'role' => UserRole::Admin,
        ]);
    }

    /**
     * A renter waiting for verification.
     */
    public function pending(): static
    {
        return $this->state(fn () => ['status' => RegistrationStatus::Pending]);
    }

    /**
     * A renter whose registration was rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn () => ['status' => RegistrationStatus::Rejected]);
    }
}
