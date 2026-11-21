<?php

namespace App\Http\Controllers\Auth;

use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Logs users in and out. Renters can only log in after the admin approved their registration.
 */
class LoginController extends Controller
{
    /**
     * Show the login form (S01).
     */
    public function showForm(): View
    {
        return view('auth.login');
    }

    /**
     * Log the user in, refusing renters whose registration is pending or rejected.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials)) {
            return $this->refuse('Email atau kata sandi salah.');
        }

        $user = $request->user();

        if (! $user->isApproved()) {
            Auth::logout();

            return $this->refuse($this->refusalMessage($user->status));
        }

        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    /**
     * Log the user out and end the session.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Return to the login form with an error under the email field.
     */
    private function refuse(string $message): RedirectResponse
    {
        return back()->withErrors(['email' => $message])->onlyInput('email');
    }

    /**
     * Get the message that explains why a renter who isn't approved cannot log in.
     */
    private function refusalMessage(RegistrationStatus $status): string
    {
        return $status === RegistrationStatus::Rejected
            ? 'Pendaftaran Anda ditolak oleh admin.'
            : 'Akun Anda masih menunggu verifikasi admin.';
    }
}
