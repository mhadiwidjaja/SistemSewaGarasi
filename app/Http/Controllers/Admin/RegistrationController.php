<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Lets the admin accept or reject new renter registrations after checking their NIK.
 */
class RegistrationController extends Controller
{
    /**
     * List renters waiting for verification, oldest first (S04).
     */
    public function index(): View
    {
        $pendingRenters = User::pendingRenters()->oldest()->get();

        return view('admin.registrations.index', ['pendingRenters' => $pendingRenters]);
    }

    /**
     * Accept a pending registration so the renter can log in.
     */
    public function approve(User $user): RedirectResponse
    {
        $this->decide($user, RegistrationStatus::Approved);

        return back()->with('success', "Pendaftaran {$user->name} diterima.");
    }

    /**
     * Reject a pending registration; the renter will not be able to log in.
     */
    public function reject(User $user): RedirectResponse
    {
        $this->decide($user, RegistrationStatus::Rejected);

        return back()->with('success', "Pendaftaran {$user->name} ditolak.");
    }

    /**
     * Save the admin's decision. Only pending renters can be decided on.
     */
    private function decide(User $user, RegistrationStatus $decision): void
    {
        abort_unless($user->isPendingRenter(), Response::HTTP_NOT_FOUND);

        $user->status = $decision;
        $user->save();
    }
}
