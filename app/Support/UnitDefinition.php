<?php

namespace App\Support;

use App\Enums\UnitDimension;

/**
 * One unit, as the merge engine needs it: what dimension it belongs to and how
 * to get from it to that dimension's canonical unit.
 */
readonly class UnitDefinition
{
    public function __construct(
        public string $symbol,
        public string $name,
        public UnitDimension $dimension,
        public float $factorToCanonical,
    ) {}

    /**
     * The plain form this is cached as. Caching the object itself would put a
     * serialised class name in a shared cache, where it outlives any deploy
     * that renames or reshapes the class — and comes back as an incomplete
     * object rather than an error you can read.
     *
     * @return array{symbol: string, name: string, dimension: string, factor: float}
     */
    public function toArray(): array
    {
        return [
            'symbol' => $this->symbol,
            'name' => $this->name,
            'dimension' => $this->dimension->value,
            'factor' => $this->factorToCanonical,
        ];
    }

    /**
     * @param  array{symbol: string, name: string, dimension: string, factor: float|int|string}  $values
     */
    public static function fromArray(array $values): self
    {
        return new self(
            symbol: $values['symbol'],
            name: $values['name'],
            dimension: UnitDimension::from($values['dimension']),
            factorToCanonical: (float) $values['factor'],
        );
    }
}
