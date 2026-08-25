<?php

use App\Models\Client;
use App\Models\Project;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('lists clients with their project counts', function () {
    $client = Client::factory()->create(['company' => 'Northlight Coffee']);
    Project::factory()->count(2)->for($client)->create();

    $this->get(route('clients.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('clients/Index')
            ->where('clients.0.company', 'Northlight Coffee')
            ->where('clients.0.projects_count', 2));
});

it('creates a client', function () {
    $this->post(route('clients.store'), [
        'name' => 'Dana Whitfield',
        'email' => 'dana@northlightcoffee.com',
        'company' => 'Northlight Coffee',
        'phone' => null,
    ])->assertRedirect();

    expect(Client::query()->where('email', 'dana@northlightcoffee.com')->exists())->toBeTrue();
});

it('rejects a duplicate email', function () {
    Client::factory()->create(['email' => 'dana@northlightcoffee.com']);

    $this->post(route('clients.store'), [
        'name' => 'Someone Else',
        'email' => 'dana@northlightcoffee.com',
    ])->assertSessionHasErrors('email');

    expect(Client::query()->count())->toBe(1);
});

it('lets a client keep their own email when editing', function () {
    $client = Client::factory()->create(['email' => 'dana@northlightcoffee.com']);

    // Without an ignore rule on the unique check, saving any other field would
    // fail unless the email were also changed.
    $this->put(route('clients.update', $client), [
        'name' => 'Dana W.',
        'email' => 'dana@northlightcoffee.com',
    ])->assertSessionHasNoErrors();

    expect($client->fresh()->name)->toBe('Dana W.');
});

it('never exposes the portal token hash to the admin panel', function () {
    $client = Client::factory()->create();
    $client->forceFill(['portal_token_hash' => hash('sha256', 'secret')])->save();

    $body = $this->get(route('clients.edit', $client))->getContent();

    expect($body)->not->toContain('portal_token_hash');
    expect($body)->not->toContain(hash('sha256', 'secret'));
});

it('deletes a client and their projects', function () {
    $client = Client::factory()->create();
    Project::factory()->for($client)->create();

    $this->delete(route('clients.destroy', $client))->assertRedirect(route('clients.index'));

    expect(Client::query()->count())->toBe(0);
    expect(Project::query()->count())->toBe(0);
});
