<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use RuntimeException;

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
        $this->configureDefaults();
    }

    /**
     * Debug mode left on in production is one of the more reliable ways to hand
     * an attacker the application's configuration: Laravel's error page renders
     * the stack trace, the loaded environment and, historically, secrets.
     *
     * Failing to boot is the right response. A site that is down gets noticed
     * and fixed in minutes; a site quietly serving its own credentials on every
     * 404 does not.
     */
    protected function refuseToRunWithDebugInProduction(): void
    {
        if (app()->isProduction() && config('app.debug') === true) {
            throw new RuntimeException(
                'APP_DEBUG must be false in production. Refusing to boot.',
            );
        }
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        $this->refuseToRunWithDebugInProduction();

        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
