<?php

namespace App\Actions;

use App\Enums\InquiryStatus;
use App\Models\Client;
use App\Models\Inquiry;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

/**
 * Turns an inquiry into a client and their first project.
 *
 * Idempotent by design: a studio owner double-clicking a button is an ordinary
 * user, not an edge case, and two clients for one inquiry is the kind of mess
 * that is discovered weeks later.
 */
class ConvertInquiry
{
    public function __construct(private readonly GrantPortalAccess $grantPortalAccess) {}

    /**
     * @return array{client: Client, project: Project, portal_token: string|null}
     *                                                                            The portal token is returned only when this call created it. A
     *                                                                            repeat call cannot return it, because only its hash was kept.
     */
    public function handle(Inquiry $inquiry): array
    {
        return DB::transaction(function () use ($inquiry): array {
            // Re-read inside the transaction and lock the row, so two concurrent
            // requests cannot both pass the guard below.
            // whereKey()->firstOrFail() rather than findOrFail(): findOrFail
            // accepts an array of keys, so its return type is a union with a
            // Collection and every property access below becomes untypeable.
            $inquiry = Inquiry::query()
                ->whereKey($inquiry->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($inquiry->isConverted()) {
                return [
                    'client' => $inquiry->convertedClient()->firstOrFail(),
                    'project' => $inquiry->convertedProject()->firstOrFail(),
                    'portal_token' => null,
                ];
            }

            $client = Client::query()->firstOrCreate(
                ['email' => $inquiry->email],
                [
                    'name' => $inquiry->name,
                    'company' => $inquiry->company,
                ],
            );

            $project = Project::query()->create([
                'client_id' => $client->getKey(),
                'title' => $this->projectTitleFor($inquiry),
                'description' => $inquiry->message,
            ]);

            $token = $client->hasActivePortalAccess()
                ? null
                : $this->grantPortalAccess->handle($client);

            $inquiry->forceFill([
                'status' => InquiryStatus::Converted,
                'converted_client_id' => $client->getKey(),
                'converted_project_id' => $project->getKey(),
                'converted_at' => now(),
            ])->save();

            return [
                'client' => $client,
                'project' => $project,
                'portal_token' => $token,
            ];
        });
    }

    /**
     * The studio renames this immediately, but an empty title in a list is
     * worse than a placeholder that says where the project came from.
     */
    private function projectTitleFor(Inquiry $inquiry): string
    {
        $subject = $inquiry->company ?? $inquiry->name;

        return "New project for {$subject}";
    }
}
