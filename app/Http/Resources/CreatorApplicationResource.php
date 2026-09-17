<?php

namespace App\Http\Resources;

use App\Models\CreatorApplication;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An application as its own applicant sees it.
 *
 * `reviewed_by` is deliberately absent and must stay that way. Which admin
 * read the pitch is bookkeeping for the panel, not part of the answer an
 * applicant is owed — the note says what would make the next attempt stronger,
 * and naming the person who wrote it only invites the argument.
 *
 * @mixin CreatorApplication
 */
class CreatorApplicationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /**
         * Read through the attribute bag rather than the magic property: the
         * model documents its status but not its timestamp, so static analysis
         * reads `reviewed_at` off $fillable as a plain string and loses the
         * datetime cast. Asking the cast what it produced keeps that honest.
         */
        $reviewedAt = $this->getAttribute('reviewed_at');

        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'pitch' => $this->pitch,
            'youtube_channel_url' => $this->youtube_channel_url,
            'review_note' => $this->review_note,
            'reviewed_at' => $reviewedAt instanceof CarbonInterface ? $reviewedAt->toISOString() : null,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
