<?php

namespace App\Enums;

enum MealSlot: string
{
    case Breakfast = 'breakfast';
    case Lunch = 'lunch';
    case Dinner = 'dinner';
    case Snack = 'snack';

    public function label(): string
    {
        return match ($this) {
            self::Breakfast => 'Breakfast',
            self::Lunch => 'Lunch',
            self::Dinner => 'Dinner',
            self::Snack => 'Snack',
        };
    }

    /**
     * The order a day is cooked in, which is not the order the cases sort in
     * alphabetically.
     */
    public function order(): int
    {
        return match ($this) {
            self::Breakfast => 0,
            self::Lunch => 1,
            self::Snack => 2,
            self::Dinner => 3,
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
