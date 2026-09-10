<?php

namespace App\DTOs;

readonly class RecipePlanItemInput
{
    public function __construct(
        public ParsedIngredient $ingredient,
        public float $servingsMultiplier = 1.0,
        public ?string $recipeTitle = null,
    ) {}
}
