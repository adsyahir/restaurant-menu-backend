<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        $this->configurePasswordPolicy();

        // In production, force HTTPS URL generation so no absolute link or
        // redirect leaks a plaintext http:// scheme.
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }
    }

    /**
     * Named rate limiters used by the API routes.
     */
    private function configureRateLimiting(): void
    {
        // General authenticated API traffic.
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by(
            $request->user()?->id ?: $request->ip()
        ));

        // Credential endpoints (login/register) — tight, keyed by email + IP to
        // slow brute-force and credential stuffing.
        RateLimiter::for('auth', fn (Request $request) => Limit::perMinute(5)->by(
            strtolower((string) $request->input('email')).'|'.$request->ip()
        ));

        // Unauthenticated public endpoints (menu, order tracking, location).
        RateLimiter::for('public', fn (Request $request) => Limit::perMinute(30)->by($request->ip()));
    }

    /**
     * Require strong passwords in production; keep tests/local lenient.
     */
    private function configurePasswordPolicy(): void
    {
        Password::defaults(function () {
            $rule = Password::min(8);

            return $this->app->isProduction()
                ? $rule->mixedCase()->numbers()->uncompromised()
                : $rule;
        });
    }
}
