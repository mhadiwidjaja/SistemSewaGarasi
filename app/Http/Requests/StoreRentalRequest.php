<?php

namespace App\Http\Requests;

use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Validates the complete rental form: the order plus vehicle details, chosen garage and proof of payment.
 */
class StoreRentalRequest extends RentalOrderRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return parent::rules() + [
            'plate_number' => [
                Rule::excludeIf(! $this->vehicleType()?->requiresPlateNumber()),
                'required', 'string', 'max:15',
            ],
            'vehicle_brand_type' => ['required', 'string', 'max:100'],
            'garage_id' => ['required', 'integer', 'exists:garages,id'],
            'payment_proof' => PaymentProofRules::RULES,
        ];
    }

    /**
     * Get custom messages for validation errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return parent::messages() + [
            'garage_id.required' => 'Pilih salah satu garasi yang tersedia.',
            'payment_proof.max' => PaymentProofRules::TOO_LARGE_MESSAGE,
        ];
    }

    /**
     * Accept plate numbers in any case and spacing, saved as e.g. "B 1234 XYZ".
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('plate_number')) {
            $this->merge(['plate_number' => Str::upper(Str::squish($this->input('plate_number')))]);
        }
    }
}
