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
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

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

    public function run(): void
    {
        User::factory()->create([
            'name' => 'Demo Staff',
            'email' => self::DEMO_EMAIL,
            'password' => Hash::make(self::DEMO_PASSWORD),
        ]);

        $grantPortalAccess = new GrantPortalAccess;
        $demoPortalToken = null;

        // Six clients, each with one or two projects, each project with a
        // milestone timeline that reads like real work rather than random rows.
        Client::factory()
            ->count(6)
            ->create()
            ->each(function (Client $client, int $index) use ($grantPortalAccess, &$demoPortalToken): void {
                $token = $grantPortalAccess->handle($client);

                // The first client's link is the one the README publishes.
                $demoPortalToken ??= $token;

                $projectCount = $index < 2 ? 2 : 1;

                Project::factory()
                    ->count($projectCount)
                    ->for($client)
                    ->create()
                    ->each($this->seedMilestones(...));
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

        foreach ($stages as $index => $stage) {
            $status = match (true) {
                $index < $doneThrough => MilestoneStatus::Done,
                $index === $doneThrough && $project->status === ProjectStatus::Active => MilestoneStatus::InProgress,
                default => MilestoneStatus::Pending,
            };

            Milestone::factory()->for($project)->create([
                'title' => $stage,
                'status' => $status,
                'position' => ($index + 1) * Milestone::POSITION_STEP,
                'due_date' => now()->addWeeks(($index + 1) * 2),
            ]);
        }
    }

    /**
     * A realistic inbox: mostly new, a few worked, one rejected.
     */
    private function seedInquiries(): void
    {
        Inquiry::factory()->count(4)->create();
        Inquiry::factory()->count(2)->status(InquiryStatus::Contacted)->create();
        Inquiry::factory()->status(InquiryStatus::Rejected)->create();
    }
}
