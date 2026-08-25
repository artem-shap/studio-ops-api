<?php

use App\Providers\AppServiceProvider;

it('refuses to boot in production with debug enabled', function () {
    config(['app.debug' => true]);
    app()->detectEnvironment(fn (): string => 'production');

    $provider = new class(app()) extends AppServiceProvider
    {
        public function runGuard(): void
        {
            $this->refuseToRunWithDebugInProduction();
        }
    };

    expect(fn () => $provider->runGuard())
        ->toThrow(RuntimeException::class, 'APP_DEBUG must be false in production');
});

it('boots in production with debug disabled', function () {
    config(['app.debug' => false]);
    app()->detectEnvironment(fn (): string => 'production');

    $provider = new class(app()) extends AppServiceProvider
    {
        public function runGuard(): void
        {
            $this->refuseToRunWithDebugInProduction();
        }
    };

    $provider->runGuard();
})->throwsNoExceptions();

it('leaves local development alone', function () {
    config(['app.debug' => true]);
    app()->detectEnvironment(fn (): string => 'local');

    $provider = new class(app()) extends AppServiceProvider
    {
        public function runGuard(): void
        {
            $this->refuseToRunWithDebugInProduction();
        }
    };

    $provider->runGuard();
})->throwsNoExceptions();
