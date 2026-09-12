<?php

namespace App\Enums;

enum FlagReason: string
{
    case Spam = 'spam';
    case Offensive = 'offensive';
    case Plagiarised = 'plagiarised';
    case Unsafe = 'unsafe';
    case WrongInformation = 'wrong_information';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Spam => 'Spam or advertising',
            self::Offensive => 'Offensive content',
            self::Plagiarised => 'Copied from elsewhere',
            self::Unsafe => 'Unsafe to cook',
            self::WrongInformation => 'Wrong information',
            self::Other => 'Something else',
        };
    }

    /**
     * Reports that describe a risk to someone cooking the dish, which is why
     * they sort to the top of the queue.
     */
    public function isUrgent(): bool
    {
        return $this === self::Unsafe;
    }

    public function color(): string
    {
        return match ($this) {
            self::Unsafe => 'danger',
            self::Offensive, self::Plagiarised => 'warning',
            default => 'gray',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
