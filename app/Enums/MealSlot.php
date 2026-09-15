<?php

namespace App\Enums;

enum MealSlot: string
{
    case Breakfast = 'breakfast';
    case Brunch = 'brunch';
    case Lunch = 'lunch';
    case Snack = 'snack';
    case Dinner = 'dinner';
    case Supper = 'supper';

    public function label(): string
    {
        return match ($this) {
            self::Breakfast => 'Breakfast',
            self::Brunch => 'Brunch',
            self::Lunch => 'Lunch',
            self::Snack => 'Snack',
            self::Dinner => 'Dinner',
            self::Supper => 'Supper',
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
            self::Brunch => 1,
            self::Lunch => 2,
            self::Snack => 3,
            self::Dinner => 4,
            self::Supper => 5,
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
