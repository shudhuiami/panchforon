<?php

namespace App\Filament\Actions;

use App\Enums\ModerationStatus;
use App\Models\Recipe;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

/**
 * What a creator does to their own recipe in the studio.
 *
 * Separate from RecipeModerationActions on purpose. That class is a moderator
 * acting on somebody else's submission, and it stamps moderated_at and
 * moderated_by because a person made a decision. This is an author sending
 * their own draft out, which is not a moderation decision at all — it is the
 * second half of the save they chose to postpone.
 */
class StudioRecipeActions
{
    /**
     * Send a draft out.
     *
     * Where it lands is ModerationStatus::forAuthor()'s answer and nobody
     * else's — the same call CreateRecipe makes when a creator saves without
     * the draft toggle, StoreRecipeRequest makes for the API, and
     * Api\RecipeController makes on its own publish endpoint. A creator who
     * writes a draft on Monday must not land in a queue they would have
     * sailed past on Tuesday, and one statement of the rule is what keeps
     * that true.
     */
    public static function publish(): Action
    {
        return Action::make('publish')
            ->label('Publish')
            ->icon(Heroicon::OutlinedRocketLaunch)
            ->color('success')
            /**
             * RecipePolicy::update() is the ability an author already needs to
             * change this recipe, and publishing a draft is a change to it.
             * The policy lets an admin past for any recipe, so ownership is
             * asserted again in visible() rather than left to it.
             */
            ->authorize('update')
            ->visible(fn (Recipe $record): bool => self::isOwnDraft($record))
            ->requiresConfirmation()
            ->modalIcon(Heroicon::OutlinedRocketLaunch)
            ->modalHeading(fn (Recipe $record): string => "Publish \"{$record->title}\"?")
            ->modalDescription(fn (): string => self::statusFor(self::viewer()) === ModerationStatus::Approved
                ? 'It goes onto the public site straight away, and you can still edit it afterwards.'
                : 'It leaves your drafts and joins the review queue. An administrator decides when it goes live.')
            ->modalSubmitActionLabel('Publish')
            ->action(function (Recipe $record): void {
                if (! self::isOwnDraft($record)) {
                    return;
                }

                $status = self::statusFor(self::viewer());

                /**
                 * The status and nothing else. moderated_at and moderated_by
                 * record that an administrator decided something, and on an
                 * author publishing their own draft nobody did.
                 */
                $record->update(['moderation_status' => $status]);

                Notification::make()
                    ->success()
                    ->title($status === ModerationStatus::Approved ? 'Recipe published' : 'Sent for review')
                    ->body($status === ModerationStatus::Approved
                        ? "\"{$record->title}\" is on the site now."
                        : "\"{$record->title}\" is with the moderators.")
                    ->send();
            });
    }

    /**
     * A draft belonging to whoever is looking at it.
     *
     * The studio's query scoping already means a foreign id never resolves,
     * so this is the second gate rather than the first. It is here anyway: an
     * admin is allowed past RecipePolicy::update() for every recipe in the
     * catalogue, and "publish" on somebody else's unfinished draft is not a
     * button they should be offered anywhere — least of all on a screen whose
     * whole promise is that it only shows you your own work.
     */
    private static function isOwnDraft(Recipe $recipe): bool
    {
        $viewer = self::viewer();

        return $viewer instanceof User
            && $recipe->isDraft()
            && $recipe->user_id !== null
            && (int) $recipe->user_id === (int) $viewer->getKey();
    }

    private static function statusFor(?User $author): ModerationStatus
    {
        return ModerationStatus::forAuthor($author, savesAsDraft: false);
    }

    private static function viewer(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }
}
