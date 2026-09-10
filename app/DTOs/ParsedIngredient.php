<?php

namespace App\DTOs;

use App\Enums\UnitDimension;

readonly class ParsedIngredient
{
    public function __construct(
        public ?float $quantity,
        public ?string $unit,
        public ?string $name,
        public string $rawText,
        public ?int $ingredientId = null,
        public ?UnitDimension $dimension = null,
    ) {}

    public function withIngredientId(int $id): self
    {
        return new self(
            quantity: $this->quantity,
            unit: $this->unit,
            name: $this->name,
            rawText: $this->rawText,
            ingredientId: $id,
            dimension: $this->dimension,
        );
    }

    public function withDimension(UnitDimension $dimension): self
    {
        return new self(
            quantity: $this->quantity,
            unit: $this->unit,
            name: $this->name,
            rawText: $this->rawText,
            ingredientId: $this->ingredientId,
            dimension: $dimension,
        );
    }
}
