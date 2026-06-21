<?php

namespace App\Providers;

use App\Http\Middleware\ActAs;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * Dev impersonation now lives in the {@see ActAs}
     * middleware so the navbar switcher can change the acting user or customer.
     */
    public function boot(): void
    {
        //
    }
}
