<?php

use App\Enums\ProjectStatus;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('creates a project, converting the budget to minor units', function () {
    $client = Client::factory()->create();

    $this->post(route('projects.store'), [
        'client_id' => $client->id,
        'title' => 'Marketing site redesign',
        'description' => 'Rebuild on a stack the studio can hand over.',
        'status' => ProjectStatus::Active->value,
        'budget' => 15000,
        'currency' => 'USD',
        'start_date' => '2026-09-01',
        'due_date' => '2026-12-01',
    ])->assertRedirect();

    // The form takes whole units; storage is cents. Getting this backwards is
    // a hundredfold error that looks plausible in both directions.
    expect(Project::query()->first()->budget_cents)->toBe(1_500_000);
});

it('rejects a due date before the start date', function () {
    $client = Client::factory()->create();

    $this->post(route('projects.store'), [
        'client_id' => $client->id,
        'title' => 'Marketing site redesign',
        'status' => ProjectStatus::Active->value,
        'currency' => 'USD',
        'start_date' => '2026-12-01',
        'due_date' => '2026-09-01',
    ])->assertSessionHasErrors('due_date');
});

it('rejects a status that is not in the enum', function () {
    $client = Client::factory()->create();

    $this->post(route('projects.store'), [
        'client_id' => $client->id,
        'title' => 'Marketing site redesign',
        'status' => 'nearly_done',
        'currency' => 'USD',
    ])->assertSessionHasErrors('status');
});

it('shows a project with its milestones in position order', function () {
    $project = Project::factory()->create();
    $project->milestones()->createMany([
        ['title' => 'Launch', 'position' => 300, 'status' => 'pending'],
        ['title' => 'Discovery', 'position' => 100, 'status' => 'done'],
        ['title' => 'Build', 'position' => 200, 'status' => 'in_progress'],
    ]);

    $this->get(route('projects.show', $project))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('projects/Show')
            ->where('milestones.0.title', 'Discovery')
            ->where('milestones.1.title', 'Build')
            ->where('milestones.2.title', 'Launch'));
});

it('round-trips the budget back into whole units for the edit form', function () {
    $project = Project::factory()->create(['budget_cents' => 1_500_000]);

    $this->get(route('projects.edit', $project))
        ->assertInertia(fn ($page) => $page->where('project.budget', 15000));
});
