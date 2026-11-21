<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The renter's garage rentals.
 */
class RentalController extends Controller
{
    /**
     * Show the logged-in renter's rental history, newest first (S09).
     */
    public function index(Request $request): View
    {
        $rentals = $request->user()->rentals()
            ->with(['garage', 'extensions'])
            ->latest()
            ->get();

        return view('rentals.index', ['rentals' => $rentals]);
    }
}
