<?php

namespace App\Actions;

use App\Models\Milestone;
use Illuminate\Support\Facades\DB;

/**
 * Swaps a milestone with its neighbour in either direction.
 *
 * Positions are unique within a project, and PostgreSQL checks that
 * immediately, so the two rows cannot simply trade values in one statement. The
 * swap parks one of them on a sentinel first, inside a transaction.
 */
class MoveMilestone
{
    /**
     * Real positions start at Milestone::POSITION_STEP, so zero is free.
     */
    private const SENTINEL = 0;

    public function handle(Milestone $milestone, Direction $direction): void
    {
        DB::transaction(function () use ($milestone, $direction): void {
            $neighbour = Milestone::query()
                ->where('project_id', $milestone->project_id)
                ->when(
                    $direction === Direction::Up,
                    fn ($query) => $query->where('position', '<', $milestone->position)->orderByDesc('position'),
                    fn ($query) => $query->where('position', '>', $milestone->position)->orderBy('position'),
                )
                ->lockForUpdate()
                ->first();

            // Already at the end it is being moved towards. Not an error.
            if ($neighbour === null) {
                return;
            }

            $from = $milestone->position;
            $to = $neighbour->position;

            $milestone->forceFill(['position' => self::SENTINEL])->save();
            $neighbour->forceFill(['position' => $from])->save();
            $milestone->forceFill(['position' => $to])->save();
        });
    }
}
