<?php

namespace App\Http\Requests;

use App\Enums\GarageType;
use App\Enums\RentalType;
use App\Enums\VehicleType;
use App\Services\RentalCalculator;
use App\Services\RentalOrder;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Rules shared by the rental quote and the rental form: vehicle type, battery, rental period and garage type.
 */
abstract class RentalOrderRequest extends FormRequest
{
    /** Battery capacity limit; matches the DECIMAL(6,2) column. */
    private const MAX_BATTERY_KWH = 9_999.99;

    /** How far ahead a rental may start, so dates stay realistic and within the database range. */
    private const MAX_START_DATE = 'today +1 year';

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $vehicleType = $this->vehicleType();

        return [
            'vehicle_type' => [$this->choiceRule(), Rule::enum(VehicleType::class)],
            'battery_capacity_kwh' => [
                Rule::excludeIf(! $vehicleType?->isElectric()),
                'required', 'decimal:0,2', 'gt:0', 'max:'.self::MAX_BATTERY_KWH,
            ],
            'battery_left_kwh' => [
                Rule::excludeIf(! $vehicleType?->isElectric()),
                'required', 'decimal:0,2', 'min:0', 'lte:battery_capacity_kwh',
            ],
            'rental_type' => ['required', Rule::enum(RentalType::class)],
            'start_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today', 'before_or_equal:'.self::MAX_START_DATE],
            'days' => [
                Rule::excludeIf($this->rentalType() !== RentalType::Daily),
                'required', 'integer', 'min:1', 'max:'.$this->maxDailyDays(),
            ],
            'garage_type' => [
                $this->choiceRule(),
                Rule::enum(GarageType::class),
                Rule::in(array_column($vehicleType?->allowedGarageTypes() ?? [], 'value')),
            ],
        ];
    }

    /**
     * Get custom messages for validation errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'battery_left_kwh.lte' => 'Sisa baterai tidak boleh melebihi kapasitas baterai.',
            'start_date.after_or_equal' => 'Tanggal mulai tidak boleh sebelum hari ini.',
            'start_date.before_or_equal' => 'Tanggal mulai paling lambat satu tahun dari hari ini.',
            'garage_type.in' => 'Jenis garasi tidak sesuai dengan jenis kendaraan.',
        ];
    }

    /**
     * Whether the vehicle type and garage type must be chosen. Saving needs them; the live quote
     * overrides this so it can already show the rental period before they are chosen.
     */
    protected function choiceRule(): string
    {
        return 'required';
    }

    /**
     * Get the longest daily rental allowed for the entered start date (today if it isn't a valid date yet).
     */
    public function maxDailyDays(): int
    {
        $start = rescue(fn () => $this->date('start_date', 'Y-m-d'), report: false) ?? CarbonImmutable::today();

        return app(RentalCalculator::class)->maxDailyDays($start);
    }

    /**
     * Build the order from the validated input.
     */
    public function order(): RentalOrder
    {
        $input = $this->validated();

        return new RentalOrder(
            vehicleType: isset($input['vehicle_type']) ? VehicleType::from($input['vehicle_type']) : null,
            plateNumber: $input['plate_number'] ?? null,
            vehicleBrandType: $input['vehicle_brand_type'] ?? null,
            batteryCapacityKwh: isset($input['battery_capacity_kwh']) ? (float) $input['battery_capacity_kwh'] : null,
            batteryLeftKwh: isset($input['battery_left_kwh']) ? (float) $input['battery_left_kwh'] : null,
            rentalType: RentalType::from($input['rental_type']),
            startDate: CarbonImmutable::createFromFormat('Y-m-d', $input['start_date'])->startOfDay(),
            days: isset($input['days']) ? (int) $input['days'] : null,
            garageType: isset($input['garage_type']) ? GarageType::from($input['garage_type']) : null,
            garageId: isset($input['garage_id']) ? (int) $input['garage_id'] : null,
        );
    }

    /**
     * Get the vehicle type from the raw input, or null when it isn't valid (yet).
     */
    protected function vehicleType(): ?VehicleType
    {
        return VehicleType::tryFrom((string) $this->input('vehicle_type'));
    }

    /**
     * Get the rental type from the raw input, or null when it isn't valid (yet).
     */
    private function rentalType(): ?RentalType
    {
        return RentalType::tryFrom((string) $this->input('rental_type'));
    }
}
