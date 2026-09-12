<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\IngredientAlias;
use App\Models\RecipeIngredient;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Folding a duplicate ingredient into the one that should have been used.
 *
 * Imports create an ingredient for any name they cannot resolve, so the
 * dictionary drifts: "corriander", "fresh coriander" and "coriander" all end
 * up as separate rows, and the shopping list stops merging them. This moves
 * every recipe line and alias across, then records the old name as an alias so
 * the next import resolves it correctly instead of creating the row again.
 */
class IngredientMerger
{
    /**
     * @return int the number of recipe lines moved
     */
    public function merge(Ingredient $from, Ingredient $into): int
    {
        if ($from->is($into)) {
            throw new RuntimeException('An ingredient cannot be merged into itself.');
        }

        return DB::transaction(function () use ($from, $into): int {
            $moved = RecipeIngredient::query()
                ->where('ingredient_id', $from->id)
                ->update(['ingredient_id' => $into->id]);

            foreach ($from->aliases as $alias) {
                $this->rememberAlias($into, $alias->alias);
            }

            // The name being retired becomes an alias, so imports resolve it.
            $this->rememberAlias($into, $from->canonical_name);

            $from->aliases()->delete();
            $from->delete();

            return $moved;
        });
    }

    private function rememberAlias(Ingredient $ingredient, string $alias): void
    {
        $normalised = mb_strtolower(trim($alias));

        if ($normalised === '' || $normalised === $ingredient->canonical_name) {
            return;
        }

        /**
         * updateOrCreate rather than firstOrCreate: an alias that already
         * belongs to the ingredient being retired has to be pointed at the
         * survivor, or it would be deleted along with its owner.
         */
        IngredientAlias::updateOrCreate(['alias' => $normalised], ['ingredient_id' => $ingredient->id]);
    }
}
