<?php

namespace Tests\Unit;

use App\Enums\GarageType;
use PHPUnit\Framework\TestCase;

/**
 * UT13: garage code prefixes.
 */
class GarageTypeTest extends TestCase
{
    /** UT13: Code prefix is S for bicycle, C for car and E for electric car. */
    public function test_code_prefix_is_s_for_bicycle_c_for_car_and_e_for_electric_car(): void
    {
        $this->assertSame('S', GarageType::Bicycle->codePrefix());
        $this->assertSame('C', GarageType::Car->codePrefix());
        $this->assertSame('E', GarageType::ElectricCar->codePrefix());
    }
}
