<?php

use App\Actions\GrantPortalAccess;
use App\Enums\MilestoneStatus;
use App\Models\Client;
use App\Models\Milestone;
use App\Models\Project;

function clientWithToken(): array
{
    $client = Client::factory()->create();
    $token = app(GrantPortalAccess::class)->handle($client);

    return [$client->fresh(), $token];
}

it('returns the client and their projects for a valid token', function () {
    [$client, $token] = clientWithToken();
    $project = Project::factory()->for($client)->create(['title' => 'Marketing site redesign']);
    Milestone::factory()->for($project)->create(['title' => 'Discovery', 'position' => 100]);

    $response = $this->withHeaders(studioKey())
        ->getJson(route('api.portal.show', $token));

    $response->assertOk();

    expect($response->json('data.client.name'))->toBe($client->name);
    expect($response->json('data.projects.0.title'))->toBe('Marketing site redesign');
    expect($response->json('data.projects.0.milestones.0.title'))->toBe('Discovery');
});

it('ships each status with its own label and colour', function () {
    [$client, $token] = clientWithToken();
    $project = Project::factory()->for($client)->create();
    Milestone::factory()->for($project)->create([
        'status' => MilestoneStatus::InProgress,
        'position' => 100,
    ]);

    $response = $this->withHeaders(studioKey())
        ->getJson(route('api.portal.show', $token));

    // The client renders these. It never owns a second status-to-colour map.
    expect($response->json('data.projects.0.milestones.0.status'))
        ->toBe(['value' => 'in_progress', 'label' => 'In progress', 'color' => 'blue']);
});

it('orders milestones by position, not by insertion', function () {
    [$client, $token] = clientWithToken();
    $project = Project::factory()->for($client)->create();

    Milestone::factory()->for($project)->create(['title' => 'Launch', 'position' => 300]);
    Milestone::factory()->for($project)->create(['title' => 'Discovery', 'position' => 100]);
    Milestone::factory()->for($project)->create(['title' => 'Build', 'position' => 200]);

    $titles = $this->withHeaders(studioKey())
        ->getJson(route('api.portal.show', $token))
        ->json('data.projects.0.milestones.*.title');

    expect($titles)->toBe(['Discovery', 'Build', 'Launch']);
});

it('never exposes the token hash', function () {
    [$client, $token] = clientWithToken();
    Project::factory()->for($client)->create();

    $body = $this->withHeaders(studioKey())
        ->getJson(route('api.portal.show', $token))
        ->getContent();

    expect($body)->not->toContain('portal_token');
    expect($body)->not->toContain($client->portal_token_hash);
});

it('404s an invalid token', function () {
    clientWithToken();

    $this->withHeaders(studioKey())
        ->getJson(route('api.portal.show', str_repeat('f', 64)))
        ->assertNotFound();
});

it('404s an expired token', function () {
    [$client, $token] = clientWithToken();
    $client->forceFill(['portal_token_expires_at' => now()->subDay()])->save();

    $this->withHeaders(studioKey())
        ->getJson(route('api.portal.show', $token))
        ->assertNotFound();
});

it('404s a revoked token', function () {
    [$client, $token] = clientWithToken();
    $client->forceFill(['portal_token_revoked_at' => now()])->save();

    $this->withHeaders(studioKey())
        ->getJson(route('api.portal.show', $token))
        ->assertNotFound();
});

it('gives invalid, expired and revoked tokens the same response', function () {
    [$expiredClient, $expiredToken] = clientWithToken();
    $expiredClient->forceFill(['portal_token_expires_at' => now()->subDay()])->save();

    [$revokedClient, $revokedToken] = clientWithToken();
    $revokedClient->forceFill(['portal_token_revoked_at' => now()])->save();

    $bodies = collect([str_repeat('f', 64), $expiredToken, $revokedToken])
        ->map(fn (string $token) => $this->withHeaders(studioKey())
            ->getJson(route('api.portal.show', $token))
            ->getContent())
        ->unique();

    // Three different reasons, one response. Distinguishing them tells a caller
    // whether a token was ever real, and whether it is worth guessing more.
    expect($bodies)->toHaveCount(1);
});

it('refuses a request with no api key', function () {
    [, $token] = clientWithToken();

    $this->getJson(route('api.portal.show', $token))
        ->assertUnauthorized();
});
