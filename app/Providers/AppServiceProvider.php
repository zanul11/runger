<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        $this->configureRateLimiting();
    }

    /**
     * Rate limiter untuk seluruh request (dipasang ke grup middleware web).
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('web', function (Request $request) {
            // User yang login (admin/sesi) lebih longgar; tamu dibatasi per-IP.
            return $request->user()
                ? Limit::perMinute(240)->by('u:' . $request->user()->id)
                : Limit::perMinute(120)->by('ip:' . $request->ip());
        });
    }
}
