<?php

namespace App\Services;

use App\DTOs\ParsedIngredient;
use App\DTOs\RecipePlanItemInput;
use App\DTOs\ShoppingListLine;
use App\Enums\UnitDimension;
use App\Support\UnitRegistry;

/**
 * Turns the ingredient rows of a week's recipes into a shopping list.
 *
 * The measurement dimension of a line comes from the unit that line is written
 * in, never from the ingredient: 400 g of potato and 3 potatoes are two things
 * to buy, not 403 of something. The units themselves come from the registry, so
 * the table is the single source of what a unit means.
 */
class MergeEngine
{
    public function __construct(private readonly UnitRegistry $units) {}

    /**
     * Merge items from recipes in a meal plan into shopping list lines.
     *
     * @param  array<int, RecipePlanItemInput>  $items
     * @return array<int, ShoppingListLine>
     */
    public function merge(array $items): array
    {
        if (empty($items)) {
            return [];
        }

        /** @var array<string, array{items: array<int, RecipePlanItemInput>, dimension: UnitDimension, ingredientId: int, name: string}> $groups */
        $groups = [];
        /** @var array<int, ShoppingListLine> $unmergedLines */
        $unmergedLines = [];

        foreach ($items as $item) {
            $ing = $item->ingredient;
            $dimension = $this->dimensionFor($ing);

            // An item is unmergeable if dimension is None, ingredientId is null, or quantity is null
            if ($dimension === UnitDimension::None || $ing->ingredientId === null || $ing->quantity === null) {
                $displayName = $ing->name ?? $ing->rawText;
                $unmergedLines[] = new ShoppingListLine(
                    ingredientId: $ing->ingredientId,
                    displayName: $displayName,
                    quantity: null,
                    unit: null,
                    isUnmerged: true,
                    sourceNote: $item->recipeTitle ? "from {$item->recipeTitle}" : null,
                    isChecked: false,
                    isOptional: $ing->isOptional,
                );

                continue;
            }

            // Group by ingredient_id and dimension.
            // Never merge across dimensions!
            $groupKey = $ing->ingredientId.'_'.$dimension->value;

            if (! isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'items' => [],
                    'dimension' => $dimension,
                    'ingredientId' => $ing->ingredientId,
                    'name' => $ing->name ?? $ing->rawText,
                ];
            }

            $groups[$groupKey]['items'][] = $item;
        }

        /** @var array<int, ShoppingListLine> $mergedLines */
        $mergedLines = [];

        foreach ($groups as $group) {
            $dimension = $group['dimension'];
            $recipeCount = count($group['items']);

            $canonicalTotal = 0.0;
            $originalUnitIfSingle = null;
            $isOptional = true;

            foreach ($group['items'] as $item) {
                $ing = $item->ingredient;
                $qty = ($ing->quantity ?? 0.0) * $item->servingsMultiplier;
                $unit = $ing->unit ?? 'piece';

                if ($recipeCount === 1) {
                    $originalUnitIfSingle = $unit;
                }

                // One recipe that really needs it makes the whole line real.
                $isOptional = $isOptional && $ing->isOptional;

                $canonicalTotal += ($qty * $this->units->factorFor($ing->unit));
            }

            // Prettify the total and pick display unit
            [$prettifiedQty, $displayUnit] = $this->prettify($canonicalTotal, $dimension, $originalUnitIfSingle);

            $sourceNote = $recipeCount > 1 ? "from {$recipeCount} recipes" : null;

            $mergedLines[] = new ShoppingListLine(
                ingredientId: $group['ingredientId'],
                displayName: $group['name'],
                quantity: $prettifiedQty,
                unit: $displayUnit,
                isUnmerged: false,
                sourceNote: $sourceNote,
                isChecked: false,
                isOptional: $isOptional,
            );
        }

        // Return merged lines followed by unmerged lines
        return array_merge($mergedLines, $unmergedLines);
    }

    /**
     * What this line is measured in: the unit decides, and only a line with no
     * unit — or one written in a unit nobody has taught us — asks the
     * ingredient what it usually is.
     */
    private function dimensionFor(ParsedIngredient $ingredient): UnitDimension
    {
        return $this->units->dimensionOf($ingredient->unit)
            ?? $ingredient->dimension
            ?? UnitDimension::None;
    }

    /**
     * Prettify the total quantity and chosen display unit based on dimension rules.
     *
     * @return array{0: float, 1: string}
     */
    protected function prettify(float $total, UnitDimension $dimension, ?string $originalUnit = null): array
    {
        switch ($dimension) {
            case UnitDimension::Mass:
                // Canonical is grams
                if ($total >= 1000.0) {
                    $kg = round($total / 1000.0, 2);

                    return [$kg, 'kg'];
                }

                return [round($total, 1), 'g'];

            case UnitDimension::Volume:
                // Canonical is ml
                if ($total >= 1000.0) {
                    $l = round($total / 1000.0, 2);

                    return [$l, 'l'];
                }

                return [round($total, 1), 'ml'];

            case UnitDimension::Count:
                // Counts round UP to whole numbers (e.g. 0.25 onion -> 1 onion)
                $rounded = (float) ceil($total);
                $unit = $originalUnit ?? 'piece';

                return [$rounded, $unit];

            case UnitDimension::None:
            default:
                return [$total, $originalUnit ?? ''];
        }
    }
}
