<?php

namespace App\Http\Resources;

use App\Models\Milestone;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Milestone
 */
class MilestoneResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'due_date' => $this->due_date?->toDateString(),

            // The enum ships its own presentation so the admin panel and the
            // portal cannot end up with two different status-to-colour maps.
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
                'color' => $this->status->color(),
            ],
        ];
    }
}
