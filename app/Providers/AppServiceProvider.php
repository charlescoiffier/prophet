<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
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
        // « Save Page Now » limite les captures : on reste bien en dessous.
        RateLimiter::for('wayback', fn () => Limit::perMinute(6));

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)
            ->by($request->user()?->getKey() ?? $request->ip()));

        // Propositions et signalements : ouverts aux visiteurs, donc limités par adresse IP.
        RateLimiter::for('proposals', fn (Request $request) => Limit::perHour(10)->by($request->ip()));

        RateLimiter::for('auth', fn (Request $request) => [
            Limit::perMinute(10)->by($request->ip()),
            Limit::perMinute(5)->by(Str::lower((string) $request->input('email')).'|'.$request->ip()),
        ]);

        Password::defaults(fn () => Password::min(10));

        Gate::define('moderate', fn (User $user) => $user->isModerator());

        // La documentation de l'API (Scramble, /docs/api) est publique : elle sert aux futurs clients.
        Gate::define('viewApiDocs', fn (?User $user = null) => true);

        // Le lien de réinitialisation mène à la page du site public (étape 5).
        ResetPassword::createUrlUsing(fn (User $user, string $token) => url('/mot-de-passe/nouveau').'?'.http_build_query([
            'token' => $token,
            'email' => $user->getEmailForPasswordReset(),
        ]));
    }
}
