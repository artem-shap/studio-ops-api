<?php

namespace App\Http\Resources;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Everything a client is allowed to see about themselves.
 *
 * Deliberately narrow: no internal identifiers beyond what the page needs, no
 * token fields, no other clients. This is the only shape that leaves the
 * application for an unauthenticated visitor holding a link.
 *
 * @mixin Client
 */
class PortalResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'client' => [
                'name' => $this->name,
                'company' => $this->company,
            ],
            'projects' => ProjectResource::collection(
                $this->whenLoaded('projects'),
            ),
        ];
    }
}
