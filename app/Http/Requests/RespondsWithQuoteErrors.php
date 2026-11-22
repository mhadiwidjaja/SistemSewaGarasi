<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

/**
 * For live quote requests: return the first validation error as JSON instead of redirecting,
 * together with the maximum daily days, so the form can always show an up-to-date hint.
 */
trait RespondsWithQuoteErrors
{
    /**
     * Get the longest daily period allowed for the entered start date.
     */
    abstract public function maxDailyDays(): int;

    /**
     * Return the first validation error as JSON.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => $validator->errors()->first(),
            'max_days' => $this->maxDailyDays(),
        ], Response::HTTP_UNPROCESSABLE_ENTITY));
    }
}
