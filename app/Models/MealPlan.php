<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\MealPlanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $starts_on
 * @property Carbon $ends_on
 * @property-read int|null $items_count
 * @property-read int|null $items_sum_servings
 * @property-read int|null $shopping_list_items_count
 * @property-read int|null $shopping_checked_count
 */
class MealPlan extends Model
{
    /** @use HasFactory<MealPlanFactory> */
    use HasFactory;

    /**
     * A plan longer than two months stops being a plan, so the range is capped.
     */
    public const MAX_DAYS = 60;

    /**
     * How long a plan runs when the app makes one on the cook's behalf.
     */
    public const DEFAULT_DAYS = 7;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'starts_on',
        'ends_on',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * A plan without a range would serialise as a calendar with no days, so
     * one is filled in rather than letting the row reach the database bare.
     */
    protected static function booted(): void
    {
        static::creating(function (MealPlan $plan): void {
            $given = $plan->getAttributes();

            if (! isset($given['starts_on'])) {
                $plan->setAttribute('starts_on', CarbonImmutable::today());
            }

            if (! isset($given['ends_on'])) {
                $plan->setAttribute('ends_on', $plan->startDate()->addDays(self::DEFAULT_DAYS - 1));
            }
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<MealPlanItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(MealPlanItem::class)
            ->orderBy('planned_for')
            ->orderBy('id');
    }

    /**
     * @return HasMany<ShoppingListItem, $this>
     */
    public function shoppingListItems(): HasMany
    {
        return $this->hasMany(ShoppingListItem::class);
    }

    /**
     * Exists so the history list can count ticked-off lines without a closure
     * inside withCount().
     *
     * @return HasMany<ShoppingListItem, $this>
     */
    public function checkedShoppingListItems(): HasMany
    {
        return $this->hasMany(ShoppingListItem::class)->where('is_checked', true);
    }

    /**
     * Inclusive length of the plan; a plan covering a single day counts as one.
     */
    public function dayCount(): int
    {
        return (int) round($this->startDate()->diffInDays($this->endDate())) + 1;
    }

    public function startDate(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->starts_on)->startOfDay();
    }

    public function endDate(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->ends_on)->startOfDay();
    }

    /**
     * Whether a given day falls inside the plan, compared as plain dates so a
     * stray time of day cannot push a date past the last midnight.
     */
    public function covers(CarbonInterface $date): bool
    {
        $day = $date->toDateString();

        return $day >= $this->startDate()->toDateString()
            && $day <= $this->endDate()->toDateString();
    }

    /**
     * The day a newly picked dish lands on by default: the plan's first day
     * while it is still ahead, otherwise today, so a dish added mid-week does
     * not appear in the past.
     */
    public function defaultPlannedDate(): CarbonImmutable
    {
        $today = CarbonImmutable::today();

        if ($this->startDate()->toDateString() >= $today->toDateString()) {
            return $this->startDate();
        }

        return $this->covers($today) ? $today : $this->endDate();
    }

    /**
     * Makes this the cook's current plan; every other plan of theirs steps down.
     */
    public function activate(): void
    {
        static::query()
            ->where('user_id', $this->user_id)
            ->whereKeyNot($this->getKey())
            ->where('is_active', true)
            ->update(['is_active' => false]);

        if ($this->is_active !== true) {
            $this->forceFill(['is_active' => true])->save();
        }
    }

    /**
     * Distinct cuisines across the plan's dishes, for the one-line summary the
     * history list shows. Reads the loaded items rather than querying again.
     *
     * @return list<string>
     */
    public function cuisineNames(): array
    {
        $seen = [];

        foreach ($this->items as $item) {
            $cuisine = $item->recipe?->cuisine;

            if (is_string($cuisine) && trim($cuisine) !== '') {
                $seen[trim($cuisine)] = true;
            }
        }

        $cuisines = array_keys($seen);
        sort($cuisines);

        return $cuisines;
    }
}
