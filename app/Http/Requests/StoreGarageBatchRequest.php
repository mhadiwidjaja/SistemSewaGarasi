<?php

namespace App\Http\Requests;

use App\Enums\GarageType;
use App\Services\GarageService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the batch garage creation form. The per-type limit of 999 is checked by GarageService.
 */
class StoreGarageBatchRequest extends FormRequest
{
    /** Upper bound for prices, so values stay realistic and fit the database column. */
    private const MAX_PRICE = 1_000_000_000;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(GarageType::class)],
            'quantity' => ['required', 'integer', 'min:1', 'max:'.GarageService::MAX_GARAGES_PER_TYPE],
            'daily_price' => ['required', 'integer', 'min:1', 'max:'.self::MAX_PRICE],
            'charging_price_per_kwh' => [
                'exclude_unless:type,'.GarageType::ElectricCar->value,
                'required', 'integer', 'min:1', 'max:'.self::MAX_PRICE,
            ],
        ];
    }

    /**
     * Get the selected garage type.
     */
    public function garageType(): GarageType
    {
        return GarageType::from($this->validated('type'));
    }

    /**
     * Get the electricity price per kWh for an electric car garage batch, or null for other types.
     */
    public function chargingPricePerKwh(): ?int
    {
        $price = $this->validated('charging_price_per_kwh');

        return $price === null ? null : (int) $price;
    }
}
