<?php

namespace App\Enums;

enum ModerationStatus: string
{
    /**
     * A recipe its author is still writing. It is private to them: no queue,
     * no catalogue, no counts. Publishing turns it into a normal submission.
     */
    case Draft = 'draft';

    case Pending = 'pending';
    case Approved = 'approved';
    case Unpublished = 'unpublished';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
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
            self::Draft => 'info',
            self::Pending => 'warning',
            self::Approved => 'success',
            self::Unpublished => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Draft => 'heroicon-m-pencil-square',
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
