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
     * Title and description travel together: a client reading their own portal
     * should see a sentence about their project, not Latin filler.
     *
     * @var array<string, string>
     */
    private const WORK = [
        'Brand identity refresh' => 'New identity, applied across signage, packaging and the site, with a short guide the team can use without us.',
        'E-commerce replatform' => 'Moving the store off the current platform, keeping every URL and rebuilding checkout so it stops losing people at the payment step.',
        'Marketing site redesign' => 'A rebuild on a stack the in-house team can edit, with the case studies restructured so the strongest work is not three clicks deep.',
        'Booking system' => 'Replacing the shared calendar with a real booking flow: availability by location, deposits, and reminders that actually go out.',
        'Customer portal' => 'A signed-in area where clients see order history and reorder in two clicks, instead of emailing for a copy of last quarter.',
        'Packaging design system' => 'One system across the whole range, so a new product does not need a designer to reach the printer.',
        'Mobile app for members' => 'Membership cards, class booking and renewals in one place, replacing three tools that do not talk to each other.',
        'Wayfinding and signage' => 'Signage for the new floor, from entrance to consulting rooms, designed so a first-time visitor never has to ask.',
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-6 months', '+1 month');

        $title = fake()->randomElement(array_keys(self::WORK));

        return [
            'client_id' => Client::factory(),
            'title' => $title,
            'description' => self::WORK[$title],
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
