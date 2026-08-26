<?php

use App\Models\User;

/**
 * The host terminates TLS and forwards the original scheme. If the proxy is not
 * trusted, every generated URL silently becomes http:// — and it looks fine
 * locally, where there is no proxy at all.
 */
it('generates https urls when the proxy says the request was secure', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->get('/', [
        'X-Forwarded-Proto' => 'https',
        'X-Forwarded-For' => '10.0.0.1',
    ]);

    expect($response->headers->get('Location'))->toStartWith('https://');
});

it('still redirects a guest to login over the forwarded scheme', function () {
    $response = $this->get('/', [
        'X-Forwarded-Proto' => 'https',
        'X-Forwarded-For' => '10.0.0.1',
    ]);

    expect($response->headers->get('Location'))
        ->toStartWith('https://')
        ->toContain('/login');
});
