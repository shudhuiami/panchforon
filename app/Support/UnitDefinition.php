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
}
