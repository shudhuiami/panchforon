<?php

namespace App\Support;

use App\Enums\UnitDimension;

/**
 * The units, in memory, keyed by symbol.
 *
 * Plain PHP on purpose: the merge engine is given one of these rather than
 * reaching for the database, so it stays testable from a literal array while
 * the application builds the same object from the units table.
 */
readonly class UnitRegistry
{
    /**
     * @param  array<string, UnitDefinition>  $units
     */
    public function __construct(private array $units = []) {}

    /**
     * @param  iterable<int, UnitDefinition>  $definitions
     */
    public static function fromDefinitions(iterable $definitions): self
    {
        $units = [];

        foreach ($definitions as $definition) {
            $units[mb_strtolower($definition->symbol)] = $definition;
        }

        return new self($units);
    }

    public function find(?string $symbol): ?UnitDefinition
    {
        if ($symbol === null || $symbol === '') {
            return null;
        }

        return $this->units[mb_strtolower(trim($symbol))] ?? null;
    }

    /**
     * The dimension a quantity in this unit belongs to. An unrecognised unit
     * has no dimension of its own — the caller decides what to fall back to.
     */
    public function dimensionOf(?string $symbol): ?UnitDimension
    {
        return $this->find($symbol)?->dimension;
    }

    /**
     * How many canonical units one of this unit is. Unknown units count as
     * themselves, which keeps a single-source line intact rather than
     * inventing a conversion for it.
     */
    public function factorFor(?string $symbol): float
    {
        $unit = $this->find($symbol);

        return $unit !== null ? $unit->factorToCanonical : 1.0;
    }

    /**
     * @return array<string, UnitDefinition>
     */
    public function all(): array
    {
        return $this->units;
    }
}
