<?php

namespace App\Http\Controllers\Auth;

use App\Enums\RegistrationStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Registers new renters. A new renter waits for the admin's verification before they can log in.
 */
class RegisterController extends Controller
{
    /**
     * Show the registration form (S02).
     */
    public function showForm(): View
    {
        return view('auth.register');
    }

    /**
     * Save the new renter with a pending status.
     */
    public function register(RegisterRequest $request): RedirectResponse
    {
        User::create([
            ...$request->validated(),
            'role' => UserRole::Renter,
            'status' => RegistrationStatus::Pending,
        ]);

        return redirect()->route('login')
            ->with('success', 'Pendaftaran berhasil. Akun Anda dapat digunakan setelah diverifikasi admin.');
    }
}
