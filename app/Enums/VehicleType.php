<?php

namespace App\Enums;

/**
 * Vehicle types a renter can park, with the rules on where each may be placed.
 */
enum VehicleType: string
{
    case Bicycle = 'bicycle';
    case Motorcycle = 'motorcycle';
    case Car = 'car';
    case ElectricCar = 'electric_car';

    /**
     * Get the garage types this vehicle may be placed in.
     *
     * Bicycles and motorcycles only fit a bicycle garage, cars only a car garage,
     * and electric cars may use either a car garage or an electric car garage.
     *
     * @return list<GarageType>
     */
    public function allowedGarageTypes(): array
    {
        return match ($this) {
            self::Bicycle, self::Motorcycle => [GarageType::Bicycle],
            self::Car => [GarageType::Car],
            self::ElectricCar => [GarageType::Car, GarageType::ElectricCar],
        };
    }

    /**
     * Determine whether a plate number must be entered (every vehicle except a bicycle).
     */
    public function requiresPlateNumber(): bool
    {
        return $this !== self::Bicycle;
    }

    /**
     * Determine whether the vehicle has a battery (capacity and remaining charge must be entered).
     */
    public function isElectric(): bool
    {
        return $this === self::ElectricCar;
    }

    /**
     * Get the display label in Bahasa Indonesia.
     */
    public function label(): string
    {
        return match ($this) {
            self::Bicycle => 'Sepeda',
            self::Motorcycle => 'Motor',
            self::Car => 'Mobil',
            self::ElectricCar => 'Mobil Listrik',
        };
    }
}
