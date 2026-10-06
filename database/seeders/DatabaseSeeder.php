<?php

namespace Database\Seeders;

use App\Actions\GrantPortalAccess;
use App\Enums\InquiryStatus;
use App\Enums\MilestoneStatus;
use App\Enums\ProjectStatus;
use App\Models\Client;
use App\Models\Inquiry;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\User;
use Database\Factories\MilestoneFactory;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use LogicException;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Credentials published in the README so a reviewer can open the deployed
     * admin panel without cloning anything. This is demo data on a demo
     * database; treat it as public.
     */
    public const DEMO_EMAIL = 'demo@studioops.dev';

    public const DEMO_PASSWORD = 'studioops';

    private int $projectsCreated = 0;

    public function run(): void
    {
        User::factory()->create([
            'name' => 'Demo Staff',
            'email' => self::DEMO_EMAIL,
            'password' => Hash::make(self::DEMO_PASSWORD),
        ]);

        $grantPortalAccess = new GrantPortalAccess;
        $demoPortalToken = null;

        // Eight titles for eight projects, each used once: a random draw puts
        // the same project name on two clients, and the dashboard shows both.
        $work = array_map(null, array_keys(ProjectFactory::WORK), ProjectFactory::WORK);
        shuffle($work);

        // Six clients, each with one or two projects, each project with a
        // milestone timeline that reads like real work rather than random rows.
        Client::factory()
            ->count(6)
            ->create()
            ->each(function (Client $client, int $index) use ($grantPortalAccess, &$demoPortalToken, &$work): void {
                $token = $grantPortalAccess->handle($client);

                // The first client's link is the one the README publishes.
                $demoPortalToken ??= $token;

                // Walk the statuses so the demo shows every state, rather than
                // whatever the random draw happened to produce. The walk starts
                // at Active so the published portal opens on work in progress.
                $statuses = [
                    ProjectStatus::Active,
                    ProjectStatus::Draft,
                    ProjectStatus::OnHold,
                    ProjectStatus::Completed,
                ];
                $projectCount = $index < 2 ? 2 : 1;

                for ($n = 0; $n < $projectCount; $n++) {
                    [$title, $description] = array_shift($work)
                        ?? throw new LogicException('The demo has more projects than distinct titles.');

                    $project = Project::factory()
                        ->for($client)
                        ->status($statuses[$this->projectsCreated++ % count($statuses)])
                        ->create(['title' => $title, 'description' => $description]);

                    $this->seedMilestones($project);
                }
            });

        $this->seedInquiries();

        $this->command->newLine();
        $this->command->info('Demo staff login: '.self::DEMO_EMAIL.' / '.self::DEMO_PASSWORD);
        $this->command->info('Demo portal path: /portal/'.$demoPortalToken);
        $this->command->newLine();
    }

    /**
     * Milestones follow the studio's real stages in order, and their statuses
     * follow the project's: a completed project has no pending milestones.
     *
     * Dates are anchored on the day of seeding, so a demo reset weeks later
     * still reads like a live studio: finished stages in the past, the current
     * one due shortly, the rest after it. The one deliberate exception is a
     * project on hold, whose stalled stage slipped a week ago, which is what
     * gives the dashboard's overdue count something true to show.
     */
    private function seedMilestones(Project $project): void
    {
        $stages = MilestoneFactory::STAGES;
        $doneThrough = match ($project->status) {
            ProjectStatus::Draft => 0,
            ProjectStatus::Active => 3,
            ProjectStatus::OnHold => 2,
            ProjectStatus::Completed => count($stages),
        };

        $currentStageDue = $project->status === ProjectStatus::OnHold ? -1 : 1;
        $dueDates = [];

        foreach ($stages as $index => $stage) {
            $status = match (true) {
                $index < $doneThrough => MilestoneStatus::Done,
                $index === $doneThrough && $project->status === ProjectStatus::Active => MilestoneStatus::InProgress,
                default => MilestoneStatus::Pending,
            };

            $dueDates[] = $dueDate = now()->startOfDay()->addWeeks(($index - $doneThrough) * 2 + $currentStageDue);

            Milestone::factory()->for($project)->create([
                'title' => $stage,
                'status' => $status,
                'position' => ($index + 1) * Milestone::POSITION_STEP,
                'due_date' => $dueDate,
            ]);
        }

        $project->update([
            'start_date' => $dueDates[0]->copy()->subWeeks(2),
            'due_date' => end($dueDates),
        ]);
    }

    /**
     * A realistic inbox: mostly new, a few worked, one rejected. Spread over
     * the past fortnight, newest unanswered, so it does not read as seven
     * messages that all arrived in the same second.
     */
    private function seedInquiries(): void
    {
        $received = fn (int $hoursAgo): array => ['created_at' => now()->subHours($hoursAgo)];

        foreach ([3, 20, 46, 70] as $hoursAgo) {
            Inquiry::factory()->create($received($hoursAgo));
        }

        foreach ([120, 190] as $hoursAgo) {
            Inquiry::factory()->status(InquiryStatus::Contacted)->create($received($hoursAgo));
        }

        Inquiry::factory()->status(InquiryStatus::Rejected)->create($received(300));
    }
}
