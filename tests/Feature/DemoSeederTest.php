<?php

use App\Enums\MilestoneStatus;
use App\Enums\ProjectStatus;
use App\Models\Milestone;
use App\Models\Project;

/**
 * The demo is what a reviewer opens first. Its dates are relative to the day
 * it was seeded, so these hold whenever `demo:reset` runs, not only on the day
 * the seeder was written.
 */
beforeEach(fn () => $this->seed());

it('puts every finished stage in the past and every open stage ahead, except a stalled one', function () {
    Milestone::query()->with('project')->get()->each(function (Milestone $milestone): void {
        if ($milestone->status === MilestoneStatus::Done) {
            expect($milestone->due_date->lt(today()))->toBeTrue();

            return;
        }

        if ($milestone->project->status !== ProjectStatus::OnHold) {
            expect($milestone->due_date->gte(today()))->toBeTrue();
        }
    });
});

it('gives the dashboard something true to count as overdue', function () {
    $overdue = Milestone::query()
        ->whereDate('due_date', '<', today())
        ->where('status', '!=', MilestoneStatus::Done)
        ->with('project')
        ->get();

    expect($overdue)->not->toBeEmpty()
        ->and($overdue->every(fn (Milestone $m) => $m->project->status === ProjectStatus::OnHold))->toBeTrue();
});

it('keeps each project window around all of its milestones', function () {
    Project::query()->with('milestones')->get()->each(function (Project $project): void {
        $dates = $project->milestones->pluck('due_date');

        expect($project->start_date->lte($dates->min()))->toBeTrue()
            ->and($project->due_date->equalTo($dates->max()))->toBeTrue();
    });
});

it('ends a completed project in the past and an open one in the future', function () {
    Project::query()->get()->each(function (Project $project): void {
        $project->status === ProjectStatus::Completed
            ? expect($project->due_date->lt(today()))->toBeTrue()
            : expect($project->due_date->gt(today()))->toBeTrue();
    });
});
