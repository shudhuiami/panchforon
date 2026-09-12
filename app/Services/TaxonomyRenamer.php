<?php

namespace App\Services;

use App\Models\Recipe;
use RuntimeException;

/**
 * Renaming a cuisine or category across the catalogue.
 *
 * Both are free text on the recipe, which is how the same cuisine ends up
 * stored as "Bangladeshi" and "bangladeshi" and splits the browse filters in
 * two. Renaming one onto an existing name merges them.
 */
class TaxonomyRenamer
{
    public const FIELDS = ['cuisine', 'category'];

    /**
     * @return int the number of recipes changed
     */
    public function rename(string $field, string $from, string $to): int
    {
        if (! in_array($field, self::FIELDS, true)) {
            throw new RuntimeException("Cannot rename [{$field}].");
        }

        $to = trim($to);

        if ($to === '') {
            throw new RuntimeException('The new name cannot be blank.');
        }

        return Recipe::query()->where($field, $from)->update([$field => $to]);
    }

    /**
     * Every value currently in use, with how many recipes carry it.
     *
     * @return array<string, int>
     */
    public function values(string $field): array
    {
        if (! in_array($field, self::FIELDS, true)) {
            throw new RuntimeException("Cannot list [{$field}].");
        }

        return Recipe::query()
            ->whereNotNull($field)
            ->where($field, '!=', '')
            ->selectRaw("{$field} as value, count(*) as aggregate")
            ->groupBy($field)
            ->orderBy('value')
            ->toBase()
            ->get()
            ->mapWithKeys(fn (object $row): array => [(string) $row->value => (int) $row->aggregate])
            ->all();
    }
}
