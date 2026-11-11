<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

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
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        ResetPassword::createUrlUsing(function (User $user, string $token) {
            return sprintf(
                '%s/reset-password?token=%s&email=%s',
                rtrim(config('app.frontend_url'), '/'),
                $token,
                urlencode($user->getEmailForPasswordReset()),
            );
        });

        $this->registerRateLimiters();
    }

    private function registerRateLimiters(): void
    {
        // Brute-force login guard: keyed by IP+email so one attacker can't
        // lock a victim's account by hammering it from a single key, while
        // still capping credential-stuffing sweeps from a single IP.
        RateLimiter::for('login', fn ($request) => Limit::perMinute(5)
            ->by($request->ip().'|'.strtolower((string) $request->input('email'))));

        // Forgot-password: capped low - each hit sends a real email, so this
        // also guards against using the endpoint to spam a victim's inbox.
        RateLimiter::for('password-reset', fn ($request) => Limit::perMinutes(10, 3)
            ->by($request->ip().'|'.strtolower((string) $request->input('email'))));

        // Reset-password token submission: defense in depth against
        // brute-forcing the reset token itself.
        RateLimiter::for('password-reset-submit', fn ($request) => Limit::perMinute(10)->by($request->ip()));

        // Baseline for every authenticated admin API call - generous enough
        // for normal SPA usage, tight enough to blunt a leaked-token or
        // runaway-frontend-loop abuse scenario.
        RateLimiter::for('admin-api', fn ($request) => Limit::perMinute(120)->by($request->user()?->id ?: $request->ip()));
    }
}
