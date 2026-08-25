<?php

use App\Http\Requests\Api\StoreInquiryRequest;
use App\Models\Inquiry;

function validInquiry(array $overrides = []): array
{
    return array_merge([
        'name' => 'Dana Whitfield',
        'email' => 'dana@northlightcoffee.com',
        'company' => 'Northlight Coffee',
        'message' => 'We are opening a second location and our booking flow cannot handle two sets of hours.',
        'budget_range' => '$15k - $40k',
        StoreInquiryRequest::HONEYPOT => '',
    ], $overrides);
}

it('accepts a valid submission', function () {
    $response = $this->withHeaders(studioKey())
        ->postJson(route('api.inquiries.store'), validInquiry());

    $response->assertCreated();

    expect(Inquiry::query()->count())->toBe(1);
    expect(Inquiry::query()->first()->email)->toBe('dana@northlightcoffee.com');
});

it('never echoes the submission back', function () {
    $response = $this->withHeaders(studioKey())
        ->postJson(route('api.inquiries.store'), validInquiry());

    // Only the identifier comes back. There is nothing useful to leak here yet,
    // but a habit of returning the model is how fields start leaking later.
    expect(array_keys($response->json()))->toBe(['id']);
});

it('rejects an empty email', function () {
    $this->withHeaders(studioKey())
        ->postJson(route('api.inquiries.store'), validInquiry(['email' => '']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');

    expect(Inquiry::query()->count())->toBe(0);
});

it('rejects a malformed email', function () {
    $this->withHeaders(studioKey())
        ->postJson(route('api.inquiries.store'), validInquiry(['email' => 'not-an-address']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');
});

it('rejects an over-long message', function () {
    $this->withHeaders(studioKey())
        ->postJson(route('api.inquiries.store'), validInquiry(['message' => str_repeat('a', 2001)]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('message');

    expect(Inquiry::query()->count())->toBe(0);
});

it('rejects a message too short to act on', function () {
    $this->withHeaders(studioKey())
        ->postJson(route('api.inquiries.store'), validInquiry(['message' => 'hi']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('message');
});

it('rejects a filled honeypot without saying why', function () {
    $response = $this->withHeaders(studioKey())
        ->postJson(route('api.inquiries.store'), validInquiry([
            StoreInquiryRequest::HONEYPOT => 'http://spam.example',
        ]));

    $response->assertUnprocessable();

    expect(Inquiry::query()->count())->toBe(0);
    expect($response->json('errors.'.StoreInquiryRequest::HONEYPOT.'.0'))
        ->toBe('This submission could not be accepted.');
});

it('rejects a submission with the honeypot field missing entirely', function () {
    $payload = validInquiry();
    unset($payload[StoreInquiryRequest::HONEYPOT]);

    $this->withHeaders(studioKey())
        ->postJson(route('api.inquiries.store'), $payload)
        ->assertUnprocessable();
});

it('refuses a request with no api key', function () {
    $this->postJson(route('api.inquiries.store'), validInquiry())
        ->assertUnauthorized();

    expect(Inquiry::query()->count())->toBe(0);
});

it('refuses a request with the wrong api key', function () {
    $this->withHeaders(['X-Studio-Key' => 'not-the-key'])
        ->postJson(route('api.inquiries.store'), validInquiry())
        ->assertUnauthorized();
});
