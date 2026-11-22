<?php

namespace App\Http\Requests;

/**
 * Validation rules for a proof of payment image, shared by the rental and extension forms.
 */
final class PaymentProofRules
{
    /** Largest accepted image, in megabytes (shown to the user and checked in the browser too). */
    public const MAX_MEGABYTES = 2;

    /** JPG or PNG image of at most MAX_MEGABYTES. */
    public const RULES = ['required', 'image', 'mimes:jpg,jpeg,png', 'max:'.(self::MAX_MEGABYTES * 1024)];

    /** Message for a file that is too large, in megabytes rather than Laravel's kilobytes. */
    public const TOO_LARGE_MESSAGE = 'Bukti pembayaran maksimal '.self::MAX_MEGABYTES.' MB.';
}
