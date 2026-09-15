<?php

namespace App\DTOs;

readonly class ShoppingListLine
{
    /**
     * @param  bool  $isOptional  True only when every recipe row behind this
     *                            line called the ingredient optional.
     */
    public function __construct(
        public ?int $ingredientId,
        public string $displayName,
        public ?float $quantity,
        public ?string $unit,
        public bool $isUnmerged = false,
        public ?string $sourceNote = null,
        public bool $isChecked = false,
        public bool $isOptional = false,
    ) {}
}
