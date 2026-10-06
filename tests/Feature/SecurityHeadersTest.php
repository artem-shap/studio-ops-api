<?php

/**
 * The panel is where staff sign in, so it gets the strict version of the
 * policy: inline scripts run only with the nonce issued for that response.
 */
function scriptSrc(string $policy): string
{
    preg_match('/script-src ([^;]+)/', $policy, $matches);

    return $matches[1] ?? '';
}

it('sends a nonce-based script policy with no inline allowance', function () {
    $policy = (string) $this->get('/login')->headers->get('Content-Security-Policy');

    expect(scriptSrc($policy))
        ->toMatch("/'nonce-[A-Za-z0-9]+'/")
        ->not->toContain("'unsafe-inline'")
        ->not->toContain("'unsafe-eval'");

    expect($policy)
        ->toContain("frame-ancestors 'none'")
        ->toContain("object-src 'none'")
        ->toContain("base-uri 'self'");
});

it('stamps every executable inline script with the nonce from the header', function () {
    $response = $this->get('/login');

    preg_match("/'nonce-([^']+)'/", (string) $response->headers->get('Content-Security-Policy'), $matches);
    $nonce = $matches[1];

    // A JSON data block is not executed, so the policy does not apply to it.
    preg_match_all('/<script(?![^>]*\bsrc=)(?![^>]*application\/json)[^>]*>/', $response->getContent(), $inline);

    expect($inline[0])->not->toBeEmpty()
        ->each->toContain("nonce=\"{$nonce}\"");
});

it('issues a different nonce on every response', function () {
    $first = $this->get('/login')->headers->get('Content-Security-Policy');
    $second = $this->get('/login')->headers->get('Content-Security-Policy');

    expect($first)->not->toBe($second);
});

it('sends HSTS only over a secure connection', function () {
    expect($this->get('https://localhost/login')->headers->get('Strict-Transport-Security'))
        ->toBe('max-age=63072000; includeSubDomains');

    expect($this->get('http://localhost/login')->headers->has('Strict-Transport-Security'))
        ->toBeFalse();
});

it('puts the same headers on API responses', function () {
    $response = $this->postJson('/api/inquiries');

    expect($response->headers->get('X-Frame-Options'))->toBe('DENY')
        ->and($response->headers->get('X-Content-Type-Options'))->toBe('nosniff')
        ->and($response->headers->has('Content-Security-Policy'))->toBeTrue();
});

it('ignores an appearance cookie that is not one of the three real values', function () {
    $response = $this->withUnencryptedCookie('appearance', "x';alert(1);//")->get('/login');

    expect($response->getContent())
        ->toContain("const appearance = 'system';")
        ->not->toContain('alert(1)');
});

it('still honours a real appearance value', function () {
    $response = $this->withUnencryptedCookie('appearance', 'dark')->get('/login');

    expect($response->getContent())->toContain("const appearance = 'dark';");
});
