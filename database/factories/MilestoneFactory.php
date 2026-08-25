<?php

namespace Database\Factories;

use App\Enums\MilestoneStatus;
use App\Models\Milestone;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Milestone>
 */
class MilestoneFactory extends Factory
{
    /**
     * The stages a design and development studio actually bills against, in the
     * order they happen. The seeder walks this list so timelines read correctly.
     *
     * @var list<string>
     */
    public const STAGES = [
        'Discovery and research',
        'Information architecture',
        'Visual design',
        'Build',
        'Content migration',
        'Launch',
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'title' => fake()->randomElement(self::STAGES),
            'due_date' => fake()->dateTimeBetween('-2 months', '+4 months'),
            'status' => fake()->randomElement(MilestoneStatus::cases()),
            'position' => Milestone::POSITION_STEP,
        ];
    }
}
