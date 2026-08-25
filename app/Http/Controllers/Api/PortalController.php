<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PortalResource;
use App\Models\Client;
use Illuminate\Support\Facades\Log;

class PortalController extends Controller
{
    public function show(string $token): PortalResource
    {
        $client = Client::findByPortalToken($token);

        if ($client === null) {
            // Invalid, expired and revoked all end here, with the same response.
            // Telling a caller which of the three it was tells them whether the
            // token was ever real.
            Log::info('Portal token rejected', [
                'token_hash_prefix' => substr(hash('sha256', $token), 0, 8),
            ]);

            abort(404);
        }

        $client->load(['projects.milestones' => fn ($query) => $query->ordered()]);

        return new PortalResource($client);
    }
}
