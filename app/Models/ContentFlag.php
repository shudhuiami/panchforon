<?php

namespace App\Models;

use App\Enums\FlagReason;
use App\Enums\FlagStatus;
use Database\Factories\ContentFlagFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A report from the community about a recipe.
 *
 * @property FlagReason $reason
 * @property FlagStatus $status
 */
class ContentFlag extends Model
{
    /** @use HasFactory<ContentFlagFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'recipe_id',
        'user_id',
        'reason',
        'note',
        'status',
        'reviewed_by',
        'reviewed_at',
        'resolution_note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reason' => FlagReason::class,
            'status' => FlagStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Recipe, $this>
     */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @param  Builder<ContentFlag>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->where('status', FlagStatus::Open);
    }

    /**
     * Close the report, recording who decided and what they decided.
     */
    public function resolve(FlagStatus $status, User $reviewer, ?string $note = null): void
    {
        $this->update([
            'status' => $status,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'resolution_note' => $note,
        ]);
    }
}
