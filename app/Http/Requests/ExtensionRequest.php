<?php

namespace App\Http\Requests;

use App\Enums\RentalType;
use App\Models\Rental;
use App\Services\RentalCalculator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Rules shared by the extension quote and the extension form. The extension starts the day after
 * the rental's current end date and follows the rental rules (daily days limited by that start month).
 */
abstract class ExtensionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'rental_type' => ['required', Rule::enum(RentalType::class)],
            'days' => [
                Rule::excludeIf(RentalType::tryFrom((string) $this->input('rental_type')) !== RentalType::Daily),
                'required', 'integer', 'min:1', 'max:'.$this->maxDailyDays(),
            ],
        ];
    }

    /**
     * Get the longest daily extension allowed, based on the month the extension starts in.
     */
    public function maxDailyDays(): int
    {
        return app(RentalCalculator::class)->maxDailyDays($this->rental()->extensionStartDate());
    }

    /**
     * Get the chosen extension type.
     */
    public function rentalType(): RentalType
    {
        return RentalType::from($this->validated('rental_type'));
    }

    /**
     * Get the number of days of a daily extension, or null for a monthly one.
     */
    public function days(): ?int
    {
        $days = $this->validated('days');

        return $days === null ? null : (int) $days;
    }

    /**
     * Get the rental being extended, from the route.
     */
    private function rental(): Rental
    {
        return $this->route('rental');
    }
}
