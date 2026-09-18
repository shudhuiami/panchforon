<?php

namespace App\Models;

use App\Enums\CreatorApplicationStatus;
use Database\Factories\CreatorApplicationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A member asking to be trusted with the catalogue.
 *
 * @property CreatorApplicationStatus $status
 * @property Carbon|null $reviewed_at
 */
class CreatorApplication extends Model
{
    /** @use HasFactory<CreatorApplicationFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'status',
        'pitch',
        'youtube_channel_url',
        'reviewed_by',
        'reviewed_at',
        'review_note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CreatorApplicationStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function applicant(): BelongsTo
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
     * @param  Builder<CreatorApplication>  $query
     */
    public function scopePending(Builder $query): void
    {
        $query->where('status', CreatorApplicationStatus::Pending);
    }

    /**
     * Close the application, recording who decided and what they decided.
     *
     * Granting the role is deliberately not done here. The record of the
     * decision and the consequence of it are two different things, and the
     * consequence belongs with the admin action that chose it, exactly as
     * unpublishing a recipe lives in the flag action rather than in the flag.
     */
    public function decide(CreatorApplicationStatus $status, User $reviewer, ?string $note = null): void
    {
        $this->update([
            'status' => $status,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_note' => $note,
        ]);
    }
}
