<?php

namespace Tests\Unit;

use App\Enums\RentalType;
use App\Services\RentalCalculator;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * UT01–UT10: rental date and fee formulas.
 */
class RentalCalculatorTest extends TestCase
{
    private RentalCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new RentalCalculator;
    }

    /**
     * @return array<string, array{string, int}>
     */
    public static function monthLengths(): array
    {
        return [
            'January' => ['2026-01-15', 31],
            'April' => ['2026-04-10', 30],
            'February 2026' => ['2026-02-01', 28],
            'February 2028 (leap year)' => ['2028-02-01', 29],
        ];
    }

    /** UT01 */
    #[DataProvider('monthLengths')]
    public function test_month_length_follows_the_start_month(string $start, int $expectedDays): void
    {
        $this->assertSame($expectedDays, $this->calculator->monthLength(CarbonImmutable::parse($start)));
    }

    /**
     * @return array<string, array{string, int}>
     */
    public static function maxDailyDays(): array
    {
        return [
            'January' => ['2026-01-15', 30],
            'April' => ['2026-04-10', 29],
            'February 2026' => ['2026-02-01', 27],
        ];
    }

    /** UT02 */
    #[DataProvider('maxDailyDays')]
    public function test_max_daily_days_is_one_less_than_month_length(string $start, int $expectedDays): void
    {
        $this->assertSame($expectedDays, $this->calculator->maxDailyDays(CarbonImmutable::parse($start)));
    }

    /** UT03 */
    public function test_daily_end_date_includes_the_start_day(): void
    {
        $end = $this->calculator->endDate(RentalType::Daily, CarbonImmutable::parse('2026-11-21'), 3);

        $this->assertSame('2026-11-23', $end->toDateString());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function monthlyEndDates(): array
    {
        return [
            'starts in January (31 days)' => ['2026-01-15', '2026-02-14'],
            'starts in April (30 days)' => ['2026-04-10', '2026-05-09'],
            'starts in February 2028 (29 days)' => ['2028-02-01', '2028-02-29'],
        ];
    }

    /** UT04 */
    #[DataProvider('monthlyEndDates')]
    public function test_monthly_end_date_lasts_the_days_of_the_start_month(string $start, string $expectedEnd): void
    {
        $end = $this->calculator->endDate(RentalType::Monthly, CarbonImmutable::parse($start), null);

        $this->assertSame($expectedEnd, $end->toDateString());
    }

    /** UT05 */
    public function test_daily_fee_is_days_times_daily_price(): void
    {
        $this->assertSame(150_000, $this->calculator->rentalFee(RentalType::Daily, 50_000, 3));
    }

    /** UT06 */
    public function test_monthly_fee_is_25_times_daily_price(): void
    {
        $this->assertSame(1_250_000, $this->calculator->rentalFee(RentalType::Monthly, 50_000, null));
    }

    /** UT07 */
    public function test_charging_fee_fills_the_battery_to_full(): void
    {
        $this->assertSame(112_500, $this->calculator->chargingFee(60, 15, 2_500));
    }

    /** UT08 */
    public function test_charging_fee_with_decimal_kwh_is_rounded_to_whole_rupiah(): void
    {
        $this->assertSame(125_625, $this->calculator->chargingFee(60.5, 10.25, 2_500));
        $this->assertSame(3, $this->calculator->chargingFee(1.25, 0, 2));
    }

    /** UT09 */
    public function test_full_battery_has_no_charging_fee(): void
    {
        $this->assertSame(0, $this->calculator->chargingFee(60, 60, 2_500));
    }

    /** UT10 */
    public function test_monthly_period_starting_on_the_31st_crosses_a_short_month(): void
    {
        $end = $this->calculator->endDate(RentalType::Monthly, CarbonImmutable::parse('2026-01-31'), null);

        $this->assertSame('2026-03-02', $end->toDateString());
    }
}
