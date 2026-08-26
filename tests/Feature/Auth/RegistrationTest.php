<?php

use App\Models\User;

/**
 * Registration is deliberately off. Anyone holding an account here can read
 * every client, every project and every inquiry, and can issue portal links —
 * so accounts are created with `php artisan staff:create`, never self-served.
 */
it('does not expose a registration screen', function () {
    $this->get('/register')->assertNotFound();
});

it('does not accept a registration attempt', function () {
    $this->post('/register', [
        'name' => 'Uninvited',
        'email' => 'uninvited@example.com',
        'password' => 'Password!2345',
        'password_confirmation' => 'Password!2345',
    ])->assertNotFound();

    expect(User::query()->where('email', 'uninvited@example.com')->exists())->toBeFalse();
});

it('creates a staff account through the console command', function () {
    $this->artisan('staff:create', ['--name' => 'Dana Whitfield', '--email' => 'dana@studioops.dev'])
        ->expectsQuestion('Password', 'Correct-Horse-Battery-9')
        ->assertSuccessful();

    expect(User::query()->where('email', 'dana@studioops.dev')->exists())->toBeTrue();
});

it('refuses a duplicate email', function () {
    User::factory()->create(['email' => 'dana@studioops.dev']);

    $this->artisan('staff:create', ['--name' => 'Someone Else', '--email' => 'dana@studioops.dev'])
        ->expectsQuestion('Password', 'Correct-Horse-Battery-9')
        ->assertFailed();

    expect(User::query()->where('email', 'dana@studioops.dev')->count())->toBe(1);
});
