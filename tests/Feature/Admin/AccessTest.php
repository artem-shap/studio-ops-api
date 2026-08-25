<?php

use App\Models\Client;
use App\Models\Project;

it('redirects guests away from the admin listings', function (string $route) {
    $this->get(route($route))->assertRedirect(route('login'));
})->with([
    'clients.index',
    'clients.create',
    'projects.index',
    'projects.create',
    'inquiries.index',
]);

it('redirects guests away from admin detail pages', function () {
    // Built inside the test rather than in a dataset: a dataset is resolved
    // before RefreshDatabase has a schema to insert into.
    $client = Client::factory()->create();
    $project = Project::factory()->for($client)->create();

    $this->get(route('clients.edit', $client))->assertRedirect(route('login'));
    $this->get(route('projects.show', $project))->assertRedirect(route('login'));
    $this->get(route('projects.edit', $project))->assertRedirect(route('login'));
});

it('refuses guest writes as well as guest reads', function () {
    $client = Client::factory()->create();

    $this->post(route('clients.store'), ['name' => 'X', 'email' => 'x@example.com'])
        ->assertRedirect(route('login'));

    $this->delete(route('clients.destroy', $client))->assertRedirect(route('login'));

    expect(Client::query()->count())->toBe(1);
});
