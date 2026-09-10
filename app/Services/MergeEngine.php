<?php

namespace App\Services;

use App\DTOs\RecipePlanItemInput;
use App\DTOs\ShoppingListLine;
use App\Enums\UnitDimension;

class MergeEngine
{
    /**
     * Mass conversions to grams (g).
     *
     * @var array<string, float>
     */
    protected const MASS_CONVERSIONS = [
        'g' => 1.0,
        'kg' => 1000.0,
        'oz' => 28.3495,
        'lb' => 453.592,
    ];

    /**
     * Volume conversions to millilitres (ml).
     *
     * @var array<string, float>
     */
    protected const VOLUME_CONVERSIONS = [
        'ml' => 1.0,
        'l' => 1000.0,
        'tsp' => 4.92892,
        'tbsp' => 14.7868,
        'cup' => 236.588,
        'floz' => 29.5735,
    ];

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

        /** @var array<string, array{items: array<int, RecipePlanItemInput>, dimension: UnitDimension, ingredientId: ?int, name: string}> $groups */
        $groups = [];
        /** @var array<int, ShoppingListLine> $unmergedLines */
        $unmergedLines = [];

        foreach ($items as $item) {
            $ing = $item->ingredient;

            // An item is unmergeable if dimension is None, ingredientId is null, or quantity is null
            if ($ing->dimension === null || $ing->dimension === UnitDimension::None || $ing->ingredientId === null || $ing->quantity === null) {
                $displayName = $ing->name ?? $ing->rawText;
                $unmergedLines[] = new ShoppingListLine(
                    ingredientId: $ing->ingredientId,
                    displayName: $displayName,
                    quantity: null,
                    unit: null,
                    isUnmerged: true,
                    sourceNote: $item->recipeTitle ? "from {$item->recipeTitle}" : null,
                    isChecked: false,
                );

                continue;
            }

            // Group by ingredient_id and dimension.
            // Never merge across dimensions!
            $groupKey = $ing->ingredientId.'_'.$ing->dimension->value;

            if (! isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'items' => [],
                    'dimension' => $ing->dimension,
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

            foreach ($group['items'] as $item) {
                $ing = $item->ingredient;
                $qty = ($ing->quantity ?? 0.0) * $item->servingsMultiplier;
                $unit = $ing->unit ?? 'piece';

                if ($recipeCount === 1) {
                    $originalUnitIfSingle = $unit;
                }

                $factor = $this->conversionFactor($unit, $dimension);
                $canonicalTotal += ($qty * $factor);
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
            );
        }

        // Return merged lines followed by unmerged lines
        return array_merge($mergedLines, $unmergedLines);
    }

    /**
     * Get conversion factor to canonical unit for a given dimension.
     */
    protected function conversionFactor(string $unit, UnitDimension $dimension): float
    {
        return match ($dimension) {
            UnitDimension::Mass => self::MASS_CONVERSIONS[$unit] ?? 1.0,
            UnitDimension::Volume => self::VOLUME_CONVERSIONS[$unit] ?? 1.0,
            UnitDimension::Count => 1.0,
            UnitDimension::None => 1.0,
        };
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
