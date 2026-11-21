<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Display formatting shared by all views, in Indonesian conventions.
 */
class Format
{
    /**
     * Format an amount as Indonesian rupiah, e.g. "Rp 150.000".
     */
    public static function rupiah(int $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }

    /**
     * Format a date as day/month/year, e.g. "21/11/2026".
     */
    public static function date(CarbonInterface $date): string
    {
        return $date->format('d/m/Y');
    }
}
