<?php

namespace App\Http\Controllers;

use App\Enums\MilestoneStatus;
use App\Enums\ProjectStatus;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Client;
use App\Models\Milestone;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(): Response
    {
        // Milestones are eager loaded and counted in PHP rather than through a
        // withCount alias. The alias would be one query cheaper, but it invents
        // a property the model does not declare, and static analysis is right
        // to object to reading something that is only sometimes there.
        $projects = Project::query()
            ->with(['client:id,name,company', 'milestones:id,project_id,status'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Project $project): array => [
                'id' => $project->id,
                'title' => $project->title,
                'client' => [
                    'id' => $project->client->id,
                    'name' => $project->client->company ?? $project->client->name,
                ],
                'status' => self::status($project->status),
                'due_date' => $project->due_date?->toDateString(),
                'milestones_count' => $project->milestones->count(),
                'done_milestones_count' => $project->milestones
                    ->where('status', MilestoneStatus::Done)
                    ->count(),
            ]);

        return Inertia::render('projects/Index', [
            'projects' => $projects,
            'statuses' => self::projectStatuses(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('projects/Form', [
            'project' => null,
            'clients' => self::clientOptions(),
            'statuses' => self::projectStatuses(),
        ]);
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $project = Project::query()->create($request->projectAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project created.')]);

        return to_route('projects.show', $project);
    }

    public function show(Project $project): Response
    {
        $project->load(['client:id,name,company,email']);

        return Inertia::render('projects/Show', [
            'project' => [
                'id' => $project->id,
                'title' => $project->title,
                'description' => $project->description,
                'status' => self::status($project->status),
                'budget_cents' => $project->budget_cents,
                'currency' => $project->currency,
                'start_date' => $project->start_date?->toDateString(),
                'due_date' => $project->due_date?->toDateString(),
                'client' => [
                    'id' => $project->client->id,
                    'name' => $project->client->name,
                    'company' => $project->client->company,
                ],
            ],
            'milestones' => $project->milestones()->ordered()->get()
                ->map(fn (Milestone $milestone): array => [
                    'id' => $milestone->id,
                    'title' => $milestone->title,
                    'due_date' => $milestone->due_date?->toDateString(),
                    'status' => [
                        'value' => $milestone->status->value,
                        'label' => $milestone->status->label(),
                        'color' => $milestone->status->color(),
                    ],
                ]),
            'milestoneStatuses' => self::milestoneStatuses(),

            // Present for exactly one render, immediately after a conversion.
            // Nothing can recover it afterwards, because only the hash was kept.
            'portalToken' => session('portal_token'),
        ]);
    }

    public function edit(Project $project): Response
    {
        return Inertia::render('projects/Form', [
            'project' => [
                'id' => $project->id,
                'client_id' => $project->client_id,
                'title' => $project->title,
                'description' => $project->description,
                'status' => $project->status->value,

                // Back to whole units for the form. Storage stays in cents.
                'budget' => $project->budget_cents !== null ? $project->budget_cents / 100 : null,
                'currency' => $project->currency,
                'start_date' => $project->start_date?->toDateString(),
                'due_date' => $project->due_date?->toDateString(),
            ],
            'clients' => self::clientOptions(),
            'statuses' => self::projectStatuses(),
        ]);
    }

    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $project->update($request->projectAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project updated.')]);

        return to_route('projects.show', $project);
    }

    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project deleted.')]);

        return to_route('projects.index');
    }

    /**
     * @return array<int, array<string, string>>
     */
    private static function clientOptions(): array
    {
        return Client::query()->orderBy('name')->get()
            ->map(fn (Client $client): array => [
                'value' => (string) $client->id,
                'label' => $client->company ?? $client->name,
            ])->all();
    }

    /**
     * Statuses reach the client already carrying their labels and colours, so
     * the frontend never keeps a second copy of the mapping.
     *
     * @return array<int, array<string, string>>
     */
    private static function projectStatuses(): array
    {
        return array_map(self::status(...), ProjectStatus::cases());
    }

    /**
     * @return array<int, array<string, string>>
     */
    private static function milestoneStatuses(): array
    {
        return array_map(
            fn (MilestoneStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
                'color' => $status->color(),
            ],
            MilestoneStatus::cases(),
        );
    }

    /**
     * @return array<string, string>
     */
    private static function status(ProjectStatus $status): array
    {
        return [
            'value' => $status->value,
            'label' => $status->label(),
            'color' => $status->color(),
        ];
    }
}
