<?php

namespace App\Http\Requests;

/**
 * Validates the live quote requested by the rental form while the renter is filling it in.
 */
class QuoteRentalRequest extends RentalOrderRequest
{
    use RespondsWithQuoteErrors;
}
