<?php

namespace App\Http\Resources;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Project
 */
class ProjectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
                'color' => $this->status->color(),
            ],

            // Minor units and a currency code. Formatting is presentation, and
            // presentation belongs to whichever client is rendering it.
            'budget_cents' => $this->budget_cents,
            'currency' => $this->currency,

            'start_date' => $this->start_date?->toDateString(),
            'due_date' => $this->due_date?->toDateString(),
            'milestones' => MilestoneResource::collection(
                $this->whenLoaded('milestones'),
            ),
        ];
    }
}
