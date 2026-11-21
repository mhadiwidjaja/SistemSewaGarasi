<?php

namespace App\Enums;

/**
 * The three garage types offered by the company.
 */
enum GarageType: string
{
    case Bicycle = 'bicycle';
    case Car = 'car';
    case ElectricCar = 'electric_car';

    /**
     * Get the letter that starts every garage code of this type (e.g. "S" in S001).
     */
    public function codePrefix(): string
    {
        return match ($this) {
            self::Bicycle => 'S',
            self::Car => 'C',
            self::ElectricCar => 'E',
        };
    }

    /**
     * Get the display label in Bahasa Indonesia.
     */
    public function label(): string
    {
        return match ($this) {
            self::Bicycle => 'Garasi Sepeda',
            self::Car => 'Garasi Mobil',
            self::ElectricCar => 'Garasi Mobil Listrik',
        };
    }
}
