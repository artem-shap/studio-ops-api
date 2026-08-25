<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case OnHold = 'on_hold';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Active => 'Active',
            self::OnHold => 'On hold',
            self::Completed => 'Completed',
        };
    }

    /**
     * Presentation lives with the value so the admin panel and the client
     * portal cannot drift apart. API Resources ship this to both.
     */
    public function color(): string
    {
        return match ($this) {
            self::Draft => 'slate',
            self::Active => 'blue',
            self::OnHold => 'amber',
            self::Completed => 'emerald',
        };
    }
}
