<?php

use App\Actions\ConvertInquiry;
use App\Enums\InquiryStatus;
use App\Models\Client;
use App\Models\Inquiry;
use App\Models\Project;

it('creates a client, a project and a portal token', function () {
    $inquiry = Inquiry::factory()->create();

    $result = app(ConvertInquiry::class)->handle($inquiry);

    expect(Client::query()->count())->toBe(1);
    expect(Project::query()->count())->toBe(1);
    expect($result['portal_token'])->toBeString()->toHaveLength(64);

    expect($result['project']->client_id)->toBe($result['client']->getKey());
});

it('stores only the hash of the portal token', function () {
    $inquiry = Inquiry::factory()->create();

    $result = app(ConvertInquiry::class)->handle($inquiry);

    $client = $result['client']->fresh();

    expect($client->portal_token_hash)->toBe(hash('sha256', $result['portal_token']));
    expect($client->portal_token_hash)->not->toBe($result['portal_token']);
});

it('marks the inquiry converted and links both records', function () {
    $inquiry = Inquiry::factory()->create();

    $result = app(ConvertInquiry::class)->handle($inquiry);
    $inquiry->refresh();

    expect($inquiry->status)->toBe(InquiryStatus::Converted);
    expect($inquiry->converted_client_id)->toBe($result['client']->getKey());
    expect($inquiry->converted_project_id)->toBe($result['project']->getKey());
    expect($inquiry->converted_at)->not->toBeNull();
});

it('creates exactly one client and one project when called twice', function () {
    $inquiry = Inquiry::factory()->create();
    $action = app(ConvertInquiry::class);

    $first = $action->handle($inquiry);
    $second = $action->handle($inquiry->fresh());

    // The whole point: a double-clicked button is an ordinary user.
    expect(Client::query()->count())->toBe(1);
    expect(Project::query()->count())->toBe(1);

    expect($second['client']->getKey())->toBe($first['client']->getKey());
    expect($second['project']->getKey())->toBe($first['project']->getKey());
});

it('cannot hand back the portal token on a repeat call', function () {
    $inquiry = Inquiry::factory()->create();
    $action = app(ConvertInquiry::class);

    $first = $action->handle($inquiry);
    $second = $action->handle($inquiry->fresh());

    // Only the hash was kept, so there is nothing left to return. Silently
    // reissuing a token instead would invalidate the link already emailed out.
    expect($first['portal_token'])->toBeString();
    expect($second['portal_token'])->toBeNull();
});

it('reuses an existing client when the email already exists', function () {
    $client = Client::factory()->create(['email' => 'dana@northlightcoffee.com']);
    $inquiry = Inquiry::factory()->create(['email' => 'dana@northlightcoffee.com']);

    $result = app(ConvertInquiry::class)->handle($inquiry);

    expect(Client::query()->count())->toBe(1);
    expect($result['client']->getKey())->toBe($client->getKey());
});
