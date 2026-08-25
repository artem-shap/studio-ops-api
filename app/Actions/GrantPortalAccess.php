<?php

namespace App\Actions;

use App\Models\Client;
use Illuminate\Support\Carbon;

/**
 * Issues a portal token for a client.
 *
 * The token is a bearer credential that travels in a URL, so it is treated like
 * a password: only its SHA-256 hash is persisted, and the plain value is
 * returned exactly once, here. Nothing else in the application can recover it.
 */
class GrantPortalAccess
{
    /**
     * How long a freshly issued link stays valid.
     */
    public const LIFETIME_DAYS = 90;

    /**
     * @return string The plain token. Show it once, then let it go.
     */
    public function handle(Client $client, ?Carbon $expiresAt = null): string
    {
        $token = bin2hex(random_bytes(32));

        $client->forceFill([
            'portal_token_hash' => hash('sha256', $token),
            'portal_token_expires_at' => $expiresAt ?? now()->addDays(self::LIFETIME_DAYS),
            'portal_token_revoked_at' => null,
        ])->save();

        return $token;
    }
}
