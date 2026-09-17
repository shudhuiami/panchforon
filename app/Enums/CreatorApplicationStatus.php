<?php

namespace App\Enums;

/**
 * Where an application to become a creator has got to.
 *
 * Named in full rather than as a bare Status because the approve action also
 * reaches for ModerationStatus, which has a Pending and an Approved of its own.
 */
enum CreatorApplicationStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Declined = 'declined';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Approved => 'Approved',
            self::Declined => 'Declined',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'success',
            self::Declined => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Pending => 'heroicon-m-clock',
            self::Approved => 'heroicon-m-check-badge',
            self::Declined => 'heroicon-m-x-circle',
        };
    }
}
