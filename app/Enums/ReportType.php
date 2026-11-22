<?php

namespace App\Enums;

/**
 * The four groupings of the garage rental report. The value is used in the report URL.
 */
enum ReportType: string
{
    case PerMonth = 'per-bulan';
    case PerDay = 'per-hari';
    case PerGarageType = 'per-jenis-garasi';
    case PerRenter = 'per-penyewa';

    /**
     * Get the tab label in Bahasa Indonesia.
     */
    public function label(): string
    {
        return match ($this) {
            self::PerMonth => 'Per Bulan',
            self::PerDay => 'Per Hari',
            self::PerGarageType => 'Per Jenis Garasi',
            self::PerRenter => 'Per Penyewa',
        };
    }

    /**
     * Get the heading of the report's first column.
     */
    public function groupHeading(): string
    {
        return match ($this) {
            self::PerMonth => 'Bulan',
            self::PerDay => 'Tanggal',
            self::PerGarageType => 'Jenis Garasi',
            self::PerRenter => 'Penyewa',
        };
    }
}
