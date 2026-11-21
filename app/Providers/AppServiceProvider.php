<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Date;
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
    }
}
