<?php

namespace App\DTOs;

readonly class ShoppingListLine
{
    public function __construct(
        public ?int $ingredientId,
        public string $displayName,
        public ?float $quantity,
        public ?string $unit,
        public bool $isUnmerged = false,
        public ?string $sourceNote = null,
        public bool $isChecked = false,
    ) {}
}
