<?php

namespace App\Http\Controllers;

use App\Enums\InquiryStatus;
use App\Enums\MilestoneStatus;
use App\Enums\ProjectStatus;
use App\Models\Inquiry;
use App\Models\Milestone;
use App\Models\Project;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * What someone opening the panel on a Monday morning actually needs: what
     * came in, what is running, and what is late.
     */
    public function __invoke(): Response
    {
        return Inertia::render('Dashboard', [
            'stats' => [
                'openInquiries' => Inquiry::query()->open()->count(),
                'activeProjects' => Project::query()->where('status', ProjectStatus::Active)->count(),
                'projectsOnHold' => Project::query()->where('status', ProjectStatus::OnHold)->count(),
                'overdueMilestones' => Milestone::query()
                    ->whereDate('due_date', '<', now())
                    ->where('status', '!=', MilestoneStatus::Done)
                    ->count(),
            ],
            'newInquiries' => Inquiry::query()
                ->where('status', InquiryStatus::New)
                ->latest()
                ->take(4)
                ->get()
                ->map(fn (Inquiry $inquiry): array => [
                    'id' => $inquiry->id,
                    'name' => $inquiry->company ?? $inquiry->name,
                    'budget_range' => $inquiry->budget_range,
                    'received_at' => $inquiry->created_at?->diffForHumans(),
                ]),
            'upcoming' => Milestone::query()
                ->with('project:id,title')
                ->where('status', '!=', MilestoneStatus::Done)
                ->whereNotNull('due_date')
                ->orderBy('due_date')
                ->take(6)
                ->get()
                ->map(fn (Milestone $milestone): array => [
                    'id' => $milestone->id,
                    'title' => $milestone->title,
                    'project' => ['id' => $milestone->project->id, 'title' => $milestone->project->title],
                    'due_date' => $milestone->due_date?->toDateString(),
                    'overdue' => $milestone->due_date?->isPast() ?? false,
                    'status' => [
                        'value' => $milestone->status->value,
                        'label' => $milestone->status->label(),
                        'color' => $milestone->status->color(),
                    ],
                ]),
        ]);
    }
}
