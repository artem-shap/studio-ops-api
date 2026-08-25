<?php

use App\Enums\MilestoneStatus;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

function milestone(Project $project, string $title, int $position): Milestone
{
    return Milestone::factory()->for($project)->create([
        'title' => $title,
        'position' => $position,
        'status' => MilestoneStatus::Pending,
    ]);
}

it('adds a milestone at the end, in steps', function () {
    $project = Project::factory()->create();
    milestone($project, 'Discovery', 100);

    $this->post(route('projects.milestones.store', $project), [
        'title' => 'Build',
        'due_date' => null,
        'status' => MilestoneStatus::Pending->value,
    ])->assertRedirect();

    expect($project->milestones()->where('title', 'Build')->value('position'))->toBe(200);
});

it('does not accept a position from the client', function () {
    $project = Project::factory()->create();

    $this->post(route('projects.milestones.store', $project), [
        'title' => 'Build',
        'status' => MilestoneStatus::Pending->value,
        'position' => 999,
    ]);

    // Position is decided by the server. Accepting it would let a client
    // collide with the unique constraint or silently reorder someone's plan.
    expect($project->milestones()->value('position'))->toBe(100);
});

it('swaps a milestone with the one above it', function () {
    $project = Project::factory()->create();
    $first = milestone($project, 'Discovery', 100);
    $second = milestone($project, 'Build', 200);

    $this->put(route('projects.milestones.move', [$project, $second, 'up']))->assertRedirect();

    expect($first->fresh()->position)->toBe(200);
    expect($second->fresh()->position)->toBe(100);
});

it('leaves the first milestone alone when moved up', function () {
    $project = Project::factory()->create();
    $first = milestone($project, 'Discovery', 100);

    $this->put(route('projects.milestones.move', [$project, $first, 'up']))->assertRedirect();

    expect($first->fresh()->position)->toBe(100);
});

it('rejects a direction that is not up or down', function () {
    $project = Project::factory()->create();
    $first = milestone($project, 'Discovery', 100);

    $this->put(route('projects.milestones.move', [$project, $first, 'sideways']))
        ->assertNotFound();
});

it('404s a milestone belonging to a different project', function () {
    $project = Project::factory()->create();
    $other = Project::factory()->create();
    $strayMilestone = milestone($other, 'Someone else work', 100);

    // Nested route parameters are bound independently, so nothing stops a
    // request naming one project and another project's milestone.
    $this->put(route('projects.milestones.update', [$project, $strayMilestone]), [
        'title' => 'Renamed',
        'status' => MilestoneStatus::Done->value,
    ])->assertNotFound();

    expect($strayMilestone->fresh()->title)->toBe('Someone else work');
});

it('404s deleting a milestone through the wrong project', function () {
    $project = Project::factory()->create();
    $other = Project::factory()->create();
    $strayMilestone = milestone($other, 'Someone else work', 100);

    $this->delete(route('projects.milestones.destroy', [$project, $strayMilestone]))
        ->assertNotFound();

    expect(Milestone::query()->count())->toBe(1);
});
