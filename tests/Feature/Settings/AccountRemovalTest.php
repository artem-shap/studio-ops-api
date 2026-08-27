<?php

use App\Models\User;

/**
 * Accounts are created and removed by the studio, never self-served. The panel
 * offering self-deletion was a way for anyone signed in — including anyone
 * using the demo credentials published in the README — to lock the studio out
 * of its own tool, because registration is closed and there is no way back in.
 */
it('does not offer account deletion from the panel', function () {
    $user = User::factory()->create();

    // 405 rather than 404: the settings path still serves GET and PATCH, so
    // the honest answer is that this method is not allowed on it.
    $this->actingAs($user)
        ->delete('/settings/profile')
        ->assertMethodNotAllowed();

    expect(User::query()->whereKey($user->getKey())->exists())->toBeTrue();
});

it('removes an account through the console command', function () {
    User::factory()->create(['email' => 'keeper@studioops.dev']);
    User::factory()->create(['email' => 'leaver@studioops.dev']);

    $this->artisan('staff:remove', ['email' => 'leaver@studioops.dev'])
        ->assertSuccessful();

    expect(User::query()->where('email', 'leaver@studioops.dev')->exists())->toBeFalse();
});

it('refuses to remove the last account', function () {
    User::factory()->create(['email' => 'only@studioops.dev']);

    $this->artisan('staff:remove', ['email' => 'only@studioops.dev'])
        ->assertFailed();

    expect(User::query()->count())->toBe(1);
});

it('reports an address that does not exist', function () {
    User::factory()->count(2)->create();

    $this->artisan('staff:remove', ['email' => 'nobody@studioops.dev'])
        ->assertFailed();

    expect(User::query()->count())->toBe(2);
});
