<?php

namespace App\Enums;

use Carbon\CarbonImmutable;

/**
 * State of a rental relative to a given day. It is derived from the rental period, never stored.
 */
enum RentalStatus: string
{
    case Upcoming = 'upcoming';
    case Active = 'active';
    case Finished = 'finished';

    /**
     * Derive the status of a rental period on the given day.
     *
     * @param  CarbonImmutable  $start  First day of the rental (inclusive).
     * @param  CarbonImmutable  $end  Last day of the rental (inclusive).
     * @param  CarbonImmutable  $today  The day to evaluate.
     */
    public static function forPeriod(CarbonImmutable $start, CarbonImmutable $end, CarbonImmutable $today): self
    {
        if ($today->lt($start->startOfDay())) {
            return self::Upcoming;
        }

        return $today->gt($end->endOfDay()) ? self::Finished : self::Active;
    }

    /**
     * Get the display label in Bahasa Indonesia.
     */
    public function label(): string
    {
        return match ($this) {
            self::Upcoming => 'Akan Datang',
            self::Active => 'Aktif',
            self::Finished => 'Selesai',
        };
    }
}
