<?php

namespace App\Http\Requests;

/**
 * Validation rules for a proof of payment image, shared by the rental and extension forms.
 */
final class PaymentProof
{
    /** JPG or PNG image of at most 2 MB (2048 KB). */
    public const RULES = ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'];
}
