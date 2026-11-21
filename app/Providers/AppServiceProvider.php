<?php

namespace App\Providers;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

/**
 * Application-wide framework settings.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Immutable dates prevent accidental changes when doing rental date math.
        Date::use(CarbonImmutable::class);

        Paginator::useBootstrapFive();

        // The admin navbar shows how many registrations are waiting for verification.
        View::composer('layouts.app', function ($view) {
            $view->with('pendingRegistrationCount', auth()->user()?->isAdmin() ? User::pendingRenters()->count() : 0);
        });
    }
}
