<?php

namespace App\Enums;

enum ModerationStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Unpublished = 'unpublished';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Awaiting review',
            self::Approved => 'Published',
            self::Unpublished => 'Unpublished',
        };
    }

    /**
     * Filament colour token used for badges in the admin panel.
     */
    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'success',
            self::Unpublished => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Pending => 'heroicon-m-clock',
            self::Approved => 'heroicon-m-check-circle',
            self::Unpublished => 'heroicon-m-eye-slash',
        };
    }

    /**
     * Statuses that are visible to the public API.
     *
     * @return array<int, string>
     */
    public static function publiclyVisible(): array
    {
        return [self::Approved->value];
    }
}
