<?php

namespace App\Http\Requests;

/**
 * Validates the complete extension form: the extension plus the renter's proof of payment.
 */
class ExtendRentalRequest extends ExtensionRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return parent::rules() + ['payment_proof' => PaymentProof::RULES];
    }
}
