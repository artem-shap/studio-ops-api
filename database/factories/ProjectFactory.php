<?php

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Models\Client;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * @var list<string>
     */
    private const TITLES = [
        'Brand identity refresh',
        'E-commerce replatform',
        'Marketing site redesign',
        'Booking system',
        'Customer portal',
        'Packaging design system',
        'Mobile app for members',
        'Wayfinding and signage',
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-6 months', '+1 month');

        return [
            'client_id' => Client::factory(),
            'title' => fake()->unique()->randomElement(self::TITLES),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement(ProjectStatus::cases()),

            // Whole thousands, in minor units: a real agency quotes round numbers.
            'budget_cents' => fake()->numberBetween(8, 60) * 100_000,
            'currency' => 'USD',

            'start_date' => $start,
            'due_date' => fake()->dateTimeBetween($start, '+5 months'),
        ];
    }

    public function status(ProjectStatus $status): static
    {
        return $this->state(fn (): array => ['status' => $status]);
    }
}
