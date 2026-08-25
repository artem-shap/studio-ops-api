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
     * Sender and message travel together. An inbox full of Faker companies
     * writing generic paragraphs reads as generated the moment anyone looks
     * at it, and the inbox is the first screen the admin panel shows.
     *
     * @var list<array{company: string, domain: string, message: string}>
     */
    private const ENQUIRIES = [
        [
            'company' => 'Northlight Coffee',
            'domain' => 'northlightcoffee.com',
            'message' => 'We are opening a second location in the spring and our site cannot handle two sets of opening hours. Looking for a rebuild before we announce it.',
        ],
        [
            'company' => 'Meridian Dental',
            'domain' => 'meridiandental.co',
            'message' => 'Our booking flow loses about half the people who start it. We would like someone to work out why and fix it, rather than redesign everything.',
        ],
        [
            'company' => 'Wildflower Bakery',
            'domain' => 'wildflowerbakery.co',
            'message' => 'We need a brand refresh before a trade show in October. Logo, colours, and a one-page site. Small budget, firm deadline.',
        ],
        [
            'company' => 'Ravenscourt Legal',
            'domain' => 'ravenscourtlegal.com',
            'message' => 'We inherited a site from a previous agency and nobody here can edit it. We want to move to something our office manager can update.',
        ],
        [
            'company' => 'Tidewater Outfitters',
            'domain' => 'tidewateroutfitters.com',
            'message' => 'Looking for a partner to design and build a members area with saved orders and one-click reordering. Roughly six weeks if that is realistic.',
        ],
        [
            'company' => 'Foxglove Interiors',
            'domain' => 'foxgloveinteriors.studio',
            'message' => 'Our packaging and our website look like two different companies. We want one system across both, and a guide so it stays that way.',
        ],
        [
            'company' => 'Beacon Hill Cycles',
            'domain' => 'beaconhillcycles.com',
            'message' => 'We sell in store and online and the stock never matches. Before any design work we probably need someone to look at how the two connect.',
        ],
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
        $enquiry = fake()->unique()->randomElement(self::ENQUIRIES);

        // Built from the parts rather than split out of a full name: fake()
        // happily returns "Mrs. Ada Whitfield", and slicing on the first space
        // produced addresses like mrs.@example.com.
        $first = fake()->firstName();
        $last = fake()->lastName();
        $handle = str($first.'.'.$last)->lower()->replaceMatches('/[^a-z.]/', '')->value();

        return [
            'name' => $first.' '.$last,
            'email' => $handle.'@'.$enquiry['domain'],
            'company' => $enquiry['company'],
            'message' => $enquiry['message'],
            'budget_range' => fake()->randomElement(self::BUDGET_RANGES),
            'status' => InquiryStatus::New,
        ];
    }

    public function status(InquiryStatus $status): static
    {
        return $this->state(fn (): array => ['status' => $status]);
    }
}
