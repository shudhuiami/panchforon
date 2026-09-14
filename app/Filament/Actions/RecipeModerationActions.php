<?php

namespace App\Filament\Actions;

use App\Enums\ModerationStatus;
use App\Models\Recipe;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Moderation for recipes.
 *
 * Recipes imported from TheMealDB are not moderated: they are not user
 * generated, and unpublishing one would silently drift the catalogue away from
 * the upstream import. Every action here is therefore limited to submissions.
 */
class RecipeModerationActions
{
    public static function approve(): Action
    {
        return Action::make('approve')
            ->label('Approve')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->authorize('moderate')
            ->requiresConfirmation()
            ->modalHeading(fn (Recipe $record): string => "Publish \"{$record->title}\"?")
            ->modalDescription('It will appear in the public recipe list and search straight away.')
            ->visible(fn (Recipe $record): bool => self::isModeratable($record)
                && $record->moderation_status !== ModerationStatus::Approved)
            ->action(function (Recipe $record): void {
                self::applyStatus($record, ModerationStatus::Approved);

                Notification::make()
                    ->success()
                    ->title('Recipe published')
                    ->body("\"{$record->title}\" is now visible to everyone.")
                    ->send();
            });
    }

    public static function unpublish(): Action
    {
        return Action::make('unpublish')
            ->label('Unpublish')
            ->icon(Heroicon::OutlinedEyeSlash)
            ->color('warning')
            ->authorize('moderate')
            ->requiresConfirmation()
            ->modalHeading(fn (Recipe $record): string => "Unpublish \"{$record->title}\"?")
            ->modalDescription('It will be hidden from the public site. The author keeps access to it and you can publish it again later.')
            ->visible(fn (Recipe $record): bool => $record->isUserSubmitted()
                && $record->moderation_status === ModerationStatus::Approved)
            ->action(function (Recipe $record): void {
                self::applyStatus($record, ModerationStatus::Unpublished);

                Notification::make()
                    ->success()
                    ->title('Recipe unpublished')
                    ->body("\"{$record->title}\" is no longer on the public site.")
                    ->send();
            });
    }

    public static function approveBulk(): BulkAction
    {
        return BulkAction::make('approveSelected')
            ->label('Approve selected')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->authorize('moderateAny')
            ->requiresConfirmation()
            ->modalHeading('Publish the selected recipes?')
            ->modalDescription('Recipes imported from TheMealDB are skipped; they are not moderated.')
            ->deselectRecordsAfterCompletion()
            ->action(fn (Collection $records) => self::applyBulkStatus($records, ModerationStatus::Approved));
    }

    public static function unpublishBulk(): BulkAction
    {
        return BulkAction::make('unpublishSelected')
            ->label('Unpublish selected')
            ->icon(Heroicon::OutlinedEyeSlash)
            ->color('warning')
            ->authorize('moderateAny')
            ->requiresConfirmation()
            ->modalHeading('Unpublish the selected recipes?')
            ->modalDescription('They will be hidden from the public site. Recipes imported from TheMealDB are skipped.')
            ->deselectRecordsAfterCompletion()
            ->action(fn (Collection $records) => self::applyBulkStatus($records, ModerationStatus::Unpublished));
    }

    /**
     * A recipe a moderator may act on: user submitted, and actually submitted.
     * A draft is its author's private work — it is not in the queue, and a
     * moderator publishing one would put writing on the site that its author
     * never sent.
     */
    private static function isModeratable(Recipe $recipe): bool
    {
        return $recipe->isUserSubmitted() && ! $recipe->isDraft();
    }

    private static function applyStatus(Recipe $recipe, ModerationStatus $status): void
    {
        $recipe->forceFill([
            'moderation_status' => $status,
            'moderated_at' => now(),
            'moderated_by' => Auth::id(),
        ])->save();
    }

    /**
     * @param  Collection<int, Recipe>  $records
     */
    private static function applyBulkStatus(Collection $records, ModerationStatus $status): void
    {
        $eligible = $records->filter(fn (Recipe $recipe): bool => self::isModeratable($recipe));

        foreach ($eligible as $recipe) {
            self::applyStatus($recipe, $status);
        }

        $skipped = $records->count() - $eligible->count();

        $notification = Notification::make()
            ->success()
            ->title($eligible->count().' '.str('recipe')->plural($eligible->count()).' updated');

        if ($skipped > 0) {
            $drafts = $records->filter(fn (Recipe $recipe): bool => $recipe->isDraft())->count();
            $imports = $skipped - $drafts;

            $reasons = [];
            if ($imports > 0) {
                $reasons[] = "{$imports} imported ".str('recipe')->plural($imports).' (TheMealDB imports are not moderated)';
            }
            if ($drafts > 0) {
                $reasons[] = "{$drafts} ".str('draft')->plural($drafts).' (their authors have not submitted them)';
            }

            $notification->body('Skipped '.implode(' and ', $reasons).'.');
        }

        $notification->send();
    }
}
