<?php

namespace Database\Factories;

use App\Enums\RentalType;
use App\Enums\VehicleType;
use App\Models\Garage;
use App\Models\Rental;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Builds rentals for tests. Defaults to a car in a car garage, 3 days from today at Rp 50.000 per day.
 *
 * @extends Factory<Rental>
 */
class RentalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'renter_id' => User::factory(),
            'garage_id' => Garage::factory(),
            'vehicle_type' => VehicleType::Car,
            'plate_number' => 'B 1234 XYZ',
            'vehicle_brand_type' => 'Toyota Avanza',
            'battery_capacity_kwh' => null,
            'battery_left_kwh' => null,
            'rental_type' => RentalType::Daily,
            'start_date' => CarbonImmutable::today(),
            'end_date' => CarbonImmutable::today()->addDays(2),
            'rental_fee' => 150_000,
            'charging_fee' => 0,
            'payment_proof_path' => 'payment-proofs/test.png',
        ];
    }

    /**
     * Set the rental period (both days inclusive).
     */
    public function between(string $start, string $end): static
    {
        return $this->state(fn () => [
            'start_date' => CarbonImmutable::parse($start),
            'end_date' => CarbonImmutable::parse($end),
        ]);
    }
}
