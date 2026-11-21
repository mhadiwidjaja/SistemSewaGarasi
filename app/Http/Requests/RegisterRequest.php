<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * Validates the renter registration form: NIK, full name, phone number, email and password.
 */
class RegisterRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'nik' => ['required', 'digits:16', 'unique:users,nik'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'regex:/^(\+62|0)[0-9]{8,13}$/'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * Accept common input variations: spaces or dashes in the phone number and uppercase emails.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone' => preg_replace('/[\s-]/', '', (string) $this->input('phone')),
            'email' => Str::lower((string) $this->input('email')),
        ]);
    }
}
