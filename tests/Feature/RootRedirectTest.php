<?php

use App\Models\User;

/**
 * The admin panel has no public face. Root is a signpost, not a page.
 */
it('sends a guest to the login screen', function () {
    $this->get('/')->assertRedirect(route('login'));
});

it('sends signed-in staff to the dashboard', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/')->assertRedirect(route('dashboard'));
});
