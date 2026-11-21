<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

/**
 * Validates the live quote requested by the rental form while the renter is filling it in.
 *
 * Errors are returned as JSON with the first message and the maximum daily days,
 * so the form can always show an up-to-date hint.
 */
class QuoteRentalRequest extends RentalOrderRequest
{
    /**
     * Return the first validation error as JSON instead of redirecting.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => $validator->errors()->first(),
            'max_days' => $this->maxDailyDays(),
        ], Response::HTTP_UNPROCESSABLE_ENTITY));
    }
}
