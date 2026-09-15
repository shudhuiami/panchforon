<?php

namespace App\Models;

use App\Enums\UnitDimension;
use App\Support\UnitDefinition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * A unit a recipe line can be written in. The symbol is the key, so a recipe
 * row stores "g" rather than an id and an unrecognised unit is simply one that
 * does not resolve.
 *
 * @property string $symbol
 * @property string $name
 * @property UnitDimension $dimension
 * @property float $factor_to_canonical
 */
class Unit extends Model
{
    public const CACHE_KEY = 'units.definitions';

    /** A day is only a safety net; a write to the table forgets the entry at once. */
    private const TTL_SECONDS = 86400;

    protected $primaryKey = 'symbol';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'symbol',
        'name',
        'dimension',
        'factor_to_canonical',
        'position',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dimension' => UnitDimension::class,
            'factor_to_canonical' => 'float',
            'position' => 'integer',
        ];
    }

    /**
     * The whole table goes stale together, so a single entry is invalidated by
     * any write rather than tracked per symbol.
     */
    protected static function booted(): void
    {
        static::saved(fn () => self::forgetDefinitions());
        static::deleted(fn () => self::forgetDefinitions());
    }

    public function toDefinition(): UnitDefinition
    {
        return new UnitDefinition(
            symbol: $this->symbol,
            name: $this->name,
            dimension: $this->dimension,
            factorToCanonical: $this->factor_to_canonical,
        );
    }

    /**
     * Every unit, cached: the table is small, changes rarely, and every
     * shopping list needs all of it.
     *
     * @return array<int, UnitDefinition>
     */
    public static function cachedDefinitions(): array
    {
        return Cache::remember(self::CACHE_KEY, self::TTL_SECONDS, fn (): array => self::query()
            ->orderBy('position')
            ->get()
            ->map(fn (self $unit): UnitDefinition => $unit->toDefinition())
            ->all());
    }

    public static function forgetDefinitions(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
