<?php

namespace App\Services;

use App\Enums\GarageType;
use App\Enums\RentalType;
use App\Enums\VehicleType;
use Carbon\CarbonImmutable;

/**
 * What a renter asks for in the rental form, already validated.
 *
 * Fields that don't apply are null: plate number for a bicycle, battery data for a non-electric
 * vehicle, days for a monthly rental. While only quoting, the vehicle details, the garage and
 * (until the renter fills them in) the vehicle type, battery data and garage type are null too.
 */
final readonly class RentalOrder
{
    /**
     * Create the order. Null means the field does not apply (see the class description).
     */
    public function __construct(
        public ?VehicleType $vehicleType,
        public ?string $plateNumber,
        public ?string $vehicleBrandType,
        public ?float $batteryCapacityKwh,
        public ?float $batteryLeftKwh,
        public RentalType $rentalType,
        public CarbonImmutable $startDate,
        public ?int $days,
        public ?GarageType $garageType,
        public ?int $garageId,
    ) {}
}
