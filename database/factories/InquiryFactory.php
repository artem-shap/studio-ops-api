<?php

namespace Database\Factories;

use App\Enums\InquiryStatus;
use App\Models\Inquiry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Inquiry>
 */
class InquiryFactory extends Factory
{
    /**
     * @var list<string>
     */
    private const MESSAGES = [
        'We are opening a second location in the spring and our current site cannot handle two sets of opening hours. Looking for a rebuild.',
        'Our booking flow loses about half the people who start it. We would like someone to look at why and fix it.',
        'We need a brand refresh before a trade show in October. Logo, colours, and a one-page site.',
        'Inherited a WordPress site from a previous agency and nobody can edit it. Want to move to something maintainable.',
        'Looking for a partner to design and build a members area with saved orders and reordering.',
        'Our packaging and our website look like two different companies. We want one system across both.',
    ];

    /**
     * @var list<string>
     */
    private const BUDGET_RANGES = [
        'Under $5k',
        '$5k - $15k',
        '$15k - $40k',
        '$40k+',
        'Not sure yet',
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'company' => fake()->company(),
            'message' => fake()->randomElement(self::MESSAGES),
            'budget_range' => fake()->randomElement(self::BUDGET_RANGES),
            'status' => InquiryStatus::New,
        ];
    }

    public function status(InquiryStatus $status): static
    {
        return $this->state(fn (): array => ['status' => $status]);
    }
}
