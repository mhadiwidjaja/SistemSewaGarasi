<?php

namespace App\Enums;

/**
 * How a garage is rented: per day or per month.
 */
enum RentalType: string
{
    case Daily = 'daily';
    case Monthly = 'monthly';

    /**
     * Get the display label in Bahasa Indonesia.
     */
    public function label(): string
    {
        return match ($this) {
            self::Daily => 'Harian',
            self::Monthly => 'Bulanan',
        };
    }
}
