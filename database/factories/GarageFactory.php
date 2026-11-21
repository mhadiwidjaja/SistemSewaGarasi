<?php

namespace Database\Factories;

use App\Enums\GarageType;
use App\Models\Garage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Builds garages for tests. Defaults to a car garage at Rp 50.000 per day.
 *
 * @extends Factory<Garage>
 */
class GarageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return $this->ofType(GarageType::Car) + [
            'daily_price' => 50_000,
            'charging_price_per_kwh' => null,
        ];
    }

    /**
     * A bicycle garage.
     */
    public function bicycle(): static
    {
        return $this->state(fn () => $this->ofType(GarageType::Bicycle));
    }

    /**
     * An electric car garage charging Rp 2.500 per kWh.
     */
    public function electricCar(): static
    {
        return $this->state(fn () => $this->ofType(GarageType::ElectricCar) + ['charging_price_per_kwh' => 2_500]);
    }

    /**
     * Get the type with a unique code that matches it.
     *
     * @return array{type: GarageType, code: string}
     */
    private function ofType(GarageType $type): array
    {
        return [
            'type' => $type,
            'code' => $type->codePrefix().fake()->unique()->numerify('###'),
        ];
    }
}
