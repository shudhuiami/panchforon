<?php

namespace App\Enums;

enum FlagStatus: string
{
    case Open = 'open';
    case Actioned = 'actioned';
    case Dismissed = 'dismissed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::Actioned => 'Actioned',
            self::Dismissed => 'Dismissed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Open => 'warning',
            self::Actioned => 'success',
            self::Dismissed => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Open => 'heroicon-m-flag',
            self::Actioned => 'heroicon-m-check-circle',
            self::Dismissed => 'heroicon-m-x-circle',
        };
    }
}
