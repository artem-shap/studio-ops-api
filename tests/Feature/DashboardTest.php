<?php

namespace Tests\Feature;

use App\Enums\MilestoneStatus;
use App\Enums\ProjectStatus;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_it_counts_what_needs_attention(): void
    {
        $this->actingAs(User::factory()->create());

        $active = Project::factory()->status(ProjectStatus::Active)->create();
        Project::factory()->status(ProjectStatus::OnHold)->create();

        // Late and unfinished counts; late and done does not.
        Milestone::factory()->for($active)->create([
            'due_date' => now()->subWeek(),
            'status' => MilestoneStatus::InProgress,
            'position' => 100,
        ]);
        Milestone::factory()->for($active)->create([
            'due_date' => now()->subWeek(),
            'status' => MilestoneStatus::Done,
            'position' => 200,
        ]);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Dashboard')
                ->where('stats.activeProjects', 1)
                ->where('stats.projectsOnHold', 1)
                ->where('stats.overdueMilestones', 1));
    }

    public function test_it_lists_the_soonest_unfinished_milestones_first(): void
    {
        $this->actingAs(User::factory()->create());

        $project = Project::factory()->create();
        Milestone::factory()->for($project)->create([
            'title' => 'Later', 'due_date' => now()->addMonth(),
            'status' => MilestoneStatus::Pending, 'position' => 200,
        ]);
        Milestone::factory()->for($project)->create([
            'title' => 'Sooner', 'due_date' => now()->addDay(),
            'status' => MilestoneStatus::Pending, 'position' => 100,
        ]);

        $this->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('upcoming.0.title', 'Sooner')
                ->where('upcoming.1.title', 'Later'));
    }
}
