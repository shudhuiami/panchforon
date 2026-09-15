<?php

namespace App\DTOs;

use App\Enums\UnitDimension;

readonly class ParsedIngredient
{
    /**
     * @param  ?string  $unit  The unit this line is measured in. It decides the
     *                         line's dimension; $dimension is only consulted
     *                         when the unit is missing or unrecognised.
     * @param  ?UnitDimension  $dimension  The fallback dimension, which is the
     *                                     ingredient's own default.
     */
    public function __construct(
        public ?float $quantity,
        public ?string $unit,
        public ?string $name,
        public string $rawText,
        public ?int $ingredientId = null,
        public ?UnitDimension $dimension = null,
        public bool $isOptional = false,
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
            isOptional: $this->isOptional,
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
            isOptional: $this->isOptional,
        );
    }
}
