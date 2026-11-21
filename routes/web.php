<?php

use App\Http\Controllers\Admin\GarageController;
use App\Http\Controllers\Admin\RegistrationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\RentalController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request) {
    $user = $request->user();

    if ($user === null) {
        return redirect()->route('login');
    }

    return redirect()->route($user->isAdmin() ? 'admin.registrations.index' : 'rentals.index');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/register', [RegisterController::class, 'showForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'role:renter'])->group(function () {
    Route::get('/rentals', [RentalController::class, 'index'])->name('rentals.index');
    Route::get('/rentals/create', [RentalController::class, 'create'])->name('rentals.create');
    Route::get('/rentals/quote', [RentalController::class, 'quote'])->name('rentals.quote');
    Route::post('/rentals', [RentalController::class, 'store'])->name('rentals.store');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/registrations', [RegistrationController::class, 'index'])->name('registrations.index');
    Route::post('/registrations/{user}/approve', [RegistrationController::class, 'approve'])->name('registrations.approve');
    Route::post('/registrations/{user}/reject', [RegistrationController::class, 'reject'])->name('registrations.reject');

    Route::get('/garages', [GarageController::class, 'index'])->name('garages.index');
    Route::get('/garages/create', [GarageController::class, 'create'])->name('garages.create');
    Route::post('/garages', [GarageController::class, 'store'])->name('garages.store');
});
