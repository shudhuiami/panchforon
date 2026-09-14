<?php

namespace App\Enums;

enum SpiceLevel: string
{
    case Mild = 'mild';
    case Medium = 'medium';
    case Hot = 'hot';

    public function label(): string
    {
        return match ($this) {
            self::Mild => 'Mild',
            self::Medium => 'Medium',
            self::Hot => 'Hot',
        };
    }

    /**
     * Filament colour token used for badges in the admin panel.
     */
    public function color(): string
    {
        return match ($this) {
            self::Mild => 'success',
            self::Medium => 'warning',
            self::Hot => 'danger',
        };
    }
}
