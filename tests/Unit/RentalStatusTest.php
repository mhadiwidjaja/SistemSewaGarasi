<?php

namespace Tests\Unit;

use App\Enums\RentalStatus;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * UT14: rental status derived from the rental period, evaluated on 21/11/2026.
 */
class RentalStatusTest extends TestCase
{
    /**
     * @return array<string, array{string, string, RentalStatus}>
     */
    public static function periods(): array
    {
        return [
            'starts tomorrow' => ['2026-11-22', '2026-11-25', RentalStatus::Upcoming],
            'covers today' => ['2026-11-20', '2026-11-23', RentalStatus::Active],
            'starts today' => ['2026-11-21', '2026-11-21', RentalStatus::Active],
            'ended yesterday' => ['2026-11-01', '2026-11-20', RentalStatus::Finished],
        ];
    }

    /** UT14 */
    #[DataProvider('periods')]
    public function test_status_is_derived_from_today(string $start, string $end, RentalStatus $expected): void
    {
        $status = RentalStatus::forPeriod(
            CarbonImmutable::parse($start),
            CarbonImmutable::parse($end),
            CarbonImmutable::parse('2026-11-21'),
        );

        $this->assertSame($expected, $status);
    }
}
