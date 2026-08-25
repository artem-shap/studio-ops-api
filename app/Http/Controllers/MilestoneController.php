<?php

namespace App\Http\Controllers;

use App\Actions\Direction;
use App\Actions\MoveMilestone;
use App\Http\Requests\StoreMilestoneRequest;
use App\Http\Requests\UpdateMilestoneRequest;
use App\Models\Milestone;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class MilestoneController extends Controller
{
    public function store(StoreMilestoneRequest $request, Project $project): RedirectResponse
    {
        $last = (int) $project->milestones()->max('position');

        $project->milestones()->create([
            ...$request->validated(),
            'position' => $last + Milestone::POSITION_STEP,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Milestone added.')]);

        return to_route('projects.show', $project);
    }

    public function update(UpdateMilestoneRequest $request, Project $project, Milestone $milestone): RedirectResponse
    {
        $this->assertBelongsToProject($project, $milestone);

        $milestone->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Milestone updated.')]);

        return to_route('projects.show', $project);
    }

    public function move(Project $project, Milestone $milestone, Direction $direction, MoveMilestone $moveMilestone): RedirectResponse
    {
        $this->assertBelongsToProject($project, $milestone);

        $moveMilestone->handle($milestone, $direction);

        return to_route('projects.show', $project);
    }

    public function destroy(Project $project, Milestone $milestone): RedirectResponse
    {
        $this->assertBelongsToProject($project, $milestone);

        $milestone->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Milestone removed.')]);

        return to_route('projects.show', $project);
    }

    /**
     * Nested route parameters are bound independently, so nothing stops a
     * request naming one project and another project's milestone. Without this
     * check that request would succeed.
     */
    private function assertBelongsToProject(Project $project, Milestone $milestone): void
    {
        abort_unless($milestone->project_id === $project->getKey(), 404);
    }
}
