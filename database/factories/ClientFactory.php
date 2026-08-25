<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    /**
     * Studios a small agency would plausibly work with. Seeded data that reads
     * like real data makes the demo legible; "Test Client 1" does not.
     *
     * @var list<string>
     */
    private const COMPANIES = [
        'Northlight Coffee',
        'Meridian Dental',
        'Harborview Architects',
        'Wildflower Bakery',
        'Ravenscourt Legal',
        'Tidewater Outfitters',
        'Foxglove Interiors',
        'Beacon Hill Cycles',
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $company = fake()->unique()->randomElement(self::COMPANIES);
        $domain = str($company)->lower()->replace(' ', '')->append('.com')->value();

        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->userName().'@'.$domain,
            'company' => $company,
            'phone' => fake()->numerify('+1 ### ### ####'),
        ];
    }

    /**
     * A client who has never been given a portal link.
     */
    public function withoutPortalAccess(): static
    {
        return $this->state(fn (): array => [
            'portal_token_hash' => null,
            'portal_token_expires_at' => null,
            'portal_token_revoked_at' => null,
        ]);
    }
}
