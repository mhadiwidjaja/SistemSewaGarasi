<?php

namespace App\Services;

use App\Enums\RentalType;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

/**
 * All rental date and fee formulas, in one place.
 *
 * Pure calculations only: no database access and no side effects.
 * The same formulas are used for a new rental and for an extension.
 */
class RentalCalculator
{
    /** The monthly rental price is this many times the daily price. */
    public const MONTHLY_PRICE_MULTIPLIER = 25;

    /**
     * Get the length of a monthly rental, which equals the number of days in its start month.
     *
     * @param  CarbonImmutable  $start  First day of the rental.
     * @return int Number of days (28–31).
     */
    public function monthLength(CarbonImmutable $start): int
    {
        return $start->daysInMonth;
    }

    /**
     * Get the maximum number of days a daily rental may last: one day less than a monthly rental.
     *
     * @param  CarbonImmutable  $start  First day of the rental.
     * @return int Number of days (27–30).
     */
    public function maxDailyDays(CarbonImmutable $start): int
    {
        return $this->monthLength($start) - 1;
    }

    /**
     * Get the last day (inclusive) of a rental period.
     *
     * @param  RentalType  $type  Daily or monthly.
     * @param  CarbonImmutable  $start  First day of the rental.
     * @param  int|null  $days  Number of days; required for daily, ignored for monthly.
     *
     * @throws InvalidArgumentException When a daily rental has no number of days.
     */
    public function endDate(RentalType $type, CarbonImmutable $start, ?int $days): CarbonImmutable
    {
        $length = $type === RentalType::Monthly
            ? $this->monthLength($start)
            : $this->requireDailyDays($days);

        return $start->addDays($length - 1);
    }

    /**
     * Get the rental fee: days × daily price for daily, or 25 × daily price for monthly.
     *
     * @param  RentalType  $type  Daily or monthly.
     * @param  int  $dailyPrice  Garage price per day in rupiah.
     * @param  int|null  $days  Number of days; required for daily, ignored for monthly.
     * @return int Fee in rupiah.
     *
     * @throws InvalidArgumentException When a daily rental has no number of days.
     */
    public function rentalFee(RentalType $type, int $dailyPrice, ?int $days): int
    {
        $chargedDays = $type === RentalType::Monthly
            ? self::MONTHLY_PRICE_MULTIPLIER
            : $this->requireDailyDays($days);

        return $chargedDays * $dailyPrice;
    }

    /**
     * Get the fee to charge an electric car's battery until full, rounded to whole rupiah.
     *
     * @param  float  $capacityKwh  Battery capacity in kWh.
     * @param  float  $remainingKwh  Charge left in the battery in kWh.
     * @param  int  $pricePerKwh  Garage electricity price per kWh in rupiah.
     * @return int Fee in rupiah.
     */
    public function chargingFee(float $capacityKwh, float $remainingKwh, int $pricePerKwh): int
    {
        return (int) round(($capacityKwh - $remainingKwh) * $pricePerKwh);
    }

    /**
     * Ensure a daily rental has a number of days.
     *
     * @throws InvalidArgumentException
     */
    private function requireDailyDays(?int $days): int
    {
        return $days ?? throw new InvalidArgumentException('A daily rental requires a number of days.');
    }
}
