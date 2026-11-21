<?php

namespace Tests\Unit;

use App\Enums\GarageType;
use App\Enums\VehicleType;
use PHPUnit\Framework\TestCase;

/**
 * UT11–UT12: vehicle placement and input rules.
 */
class VehicleTypeTest extends TestCase
{
    /** UT11 */
    public function test_each_vehicle_fits_only_its_allowed_garage_types(): void
    {
        $this->assertSame([GarageType::Bicycle], VehicleType::Bicycle->allowedGarageTypes());
        $this->assertSame([GarageType::Bicycle], VehicleType::Motorcycle->allowedGarageTypes());
        $this->assertSame([GarageType::Car], VehicleType::Car->allowedGarageTypes());
        $this->assertSame([GarageType::Car, GarageType::ElectricCar], VehicleType::ElectricCar->allowedGarageTypes());
    }

    /** UT12 */
    public function test_plate_number_is_required_for_every_vehicle_except_bicycle(): void
    {
        $this->assertFalse(VehicleType::Bicycle->requiresPlateNumber());
        $this->assertTrue(VehicleType::Motorcycle->requiresPlateNumber());
        $this->assertTrue(VehicleType::Car->requiresPlateNumber());
        $this->assertTrue(VehicleType::ElectricCar->requiresPlateNumber());
    }
}
