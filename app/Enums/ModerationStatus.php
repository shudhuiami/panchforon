<?php

namespace App\Enums;

use App\Models\User;

enum ModerationStatus: string
{
    /**
     * A recipe its author is still writing. It is private to them: no queue,
     * no catalogue, no counts. Publishing turns it into a normal submission.
     */
    case Draft = 'draft';

    case Pending = 'pending';
    case Approved = 'approved';
    case Unpublished = 'unpublished';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Pending => 'Awaiting review',
            self::Approved => 'Published',
            self::Unpublished => 'Unpublished',
        };
    }

    /**
     * Filament colour token used for badges in the admin panel.
     */
    public function color(): string
    {
        return match ($this) {
            self::Draft => 'info',
            self::Pending => 'warning',
            self::Approved => 'success',
            self::Unpublished => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Draft => 'heroicon-m-pencil-square',
            self::Pending => 'heroicon-m-clock',
            self::Approved => 'heroicon-m-check-circle',
            self::Unpublished => 'heroicon-m-eye-slash',
        };
    }

    /**
     * What a recipe becomes when its author saves it.
     *
     * The one statement of the rule. StoreRecipeRequest asks it for a new
     * submission over the API, Api\RecipeController asks it when a draft is
     * published later, and the Creator Studio asks it when a creator saves a
     * recipe from the panel — so a recipe cannot land in a review queue by
     * one route that it would have sailed past by another.
     *
     * A creator writes straight into the catalogue; that is the point of the
     * role, so their recipe lands approved rather than queued. The Pending arm
     * is for an author who may post but is not trusted to skip review — today
     * a suspended creator, and anyone the API lets through without a role.
     *
     * moderated_at and moderated_by stay null in every arm. They record that
     * an admin made a decision, and on an auto-publish nobody did.
     */
    public static function forAuthor(?User $author, bool $savesAsDraft): self
    {
        if ($savesAsDraft) {
            return self::Draft;
        }

        return $author?->canPublishWithoutReview()
            ? self::Approved
            : self::Pending;
    }

    /**
     * Statuses that are visible to the public API.
     *
     * @return array<int, string>
     */
    public static function publiclyVisible(): array
    {
        return [self::Approved->value];
    }
}
