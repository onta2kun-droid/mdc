<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Date;
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
     */
    public function boot(): void
    {
        // Format tanggal Indonesia (l, d F Y) untuk Carbon::translatedFormat().
        Carbon::setLocale(config('app.locale', 'id'));
        Date::use(\Illuminate\Support\Carbon::class);
    }
}
