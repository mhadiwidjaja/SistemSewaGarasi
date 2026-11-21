<?php

namespace App\Http\Requests;

/**
 * Validates the live quote requested by the extension form while the admin is filling it in.
 */
class QuoteExtensionRequest extends ExtensionRequest
{
    use RespondsWithQuoteErrors;
}
