<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\IngredientAlias;
use App\Models\Recipe;
use App\Models\RecipeIngredient;

/**
 * Turns ingredient lines as a person wrote them into recipe_ingredients rows.
 *
 * Lifted out of Api\RecipeController when the Creator Studio grew a repeater
 * over the same relation. Two paths writing the same table is two chances to
 * resolve "2 tbsp mustard oil" to a different canonical ingredient, and the
 * merge engine and shopping list both assume there is only ever one.
 */
class RecipeIngredientWriter
{
    public function __construct(protected IngredientParser $parser) {}

    /**
     * Write a recipe's ingredient rows, in the order they were given.
     *
     * @param  array<int|string, mixed>  $rows
     */
    public function write(Recipe $recipe, array $rows): void
    {
        $position = 0;

        foreach ($rows as $row) {
            RecipeIngredient::create([
                'recipe_id' => $recipe->id,
                ...$this->rowAttributes((array) $row, $position++),
            ]);
        }
    }

    /**
     * Replace every row a recipe has with the ones given.
     *
     * @param  array<int|string, mixed>  $rows
     */
    public function replace(Recipe $recipe, array $rows): void
    {
        $recipe->ingredients()->delete();

        $this->write($recipe, $rows);
    }

    /**
     * One written line as recipe_ingredients columns.
     *
     * A row can arrive structured (name, quantity, unit) or as one line of
     * free text; the parser fills in whatever was not sent, and anything the
     * client stated explicitly wins over what was parsed out of the text.
     * is_optional and note belong to the row rather than to the ingredient, so
     * they are taken verbatim and never inferred.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public function rowAttributes(array $row, int $position): array
    {
        $rawText = ! empty($row['raw_text']) ? trim((string) $row['raw_text']) : '';
        $customName = ! empty($row['name']) ? trim((string) $row['name']) : '';
        $quantity = isset($row['quantity']) && $row['quantity'] !== '' ? (float) $row['quantity'] : null;
        $unit = ! empty($row['unit']) ? trim((string) $row['unit']) : null;
        $note = ! empty($row['note']) ? trim((string) $row['note']) : null;

        if ($rawText === '' && $customName !== '') {
            $rawText = trim(($quantity !== null ? "{$quantity} " : '').($unit ? "{$unit} " : '').$customName);
        }

        $parsed = $this->parser->parse(
            $rawText !== '' ? $rawText : $customName,
            fn (string $name): ?int => $this->resolveOrCreate($name),
        );

        return [
            'ingredient_id' => $parsed->ingredientId,
            'quantity' => $quantity ?? $parsed->quantity,
            'unit' => $unit ?? $parsed->unit,
            'is_optional' => ! empty($row['is_optional']),
            'note' => $note,
            'raw_text' => $rawText !== '' ? $rawText : ($customName ?: 'Ingredient'),
            'position' => $position,
        ];
    }

    /**
     * The canonical ingredient a written name means.
     *
     * Three steps, in order: the canonical names themselves, then the alias
     * table — which is where "coriander leaves" reaches "cilantro" — and only
     * when both miss is a new canonical ingredient created. Creating first
     * would fork every spelling into its own ingredient and quietly stop two
     * recipes from sharing a shopping list line.
     */
    public function resolveOrCreate(string $name): ?int
    {
        $normalized = mb_strtolower(trim($name));

        if ($normalized === '') {
            return null;
        }

        $canonical = Ingredient::where('canonical_name', $normalized)->first();
        if ($canonical) {
            return $canonical->id;
        }

        $alias = IngredientAlias::where('alias', $normalized)->first();
        if ($alias) {
            return $alias->ingredient_id;
        }

        $created = Ingredient::create([
            'canonical_name' => $normalized,
            'default_dimension' => 'none',
        ]);

        return $created->id;
    }
}
