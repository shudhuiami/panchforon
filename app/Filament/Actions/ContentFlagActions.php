<?php

namespace App\Filament\Actions;

use App\Enums\FlagStatus;
use App\Enums\ModerationStatus;
use App\Models\ContentFlag;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Deciding what to do with a report.
 *
 * A moderator has three ways out: unpublish the recipe (which closes every
 * open report against it at once), mark the report actioned without touching
 * the recipe, or dismiss it.
 */
class ContentFlagActions
{
    public static function unpublishRecipe(): Action
    {
        return Action::make('unpublishRecipe')
            ->label('Unpublish recipe')
            ->icon(Heroicon::OutlinedEyeSlash)
            ->color('danger')
            ->authorize('resolve')
            ->requiresConfirmation()
            ->modalHeading(fn (ContentFlag $record): string => 'Unpublish "'.$record->recipe->title.'"?')
            ->modalDescription('It disappears from the public site, and every open report against it is closed.')
            ->schema([
                Textarea::make('resolution_note')
                    ->label('Note for the record')
                    ->placeholder('Why this was the right call.')
                    ->rows(2),
            ])
            ->visible(fn (ContentFlag $record): bool => $record->status === FlagStatus::Open
                && $record->recipe->isUserSubmitted()
                && $record->recipe->moderation_status === ModerationStatus::Approved)
            ->action(function (ContentFlag $record, array $data): void {
                $recipe = $record->recipe;

                $recipe->forceFill([
                    'moderation_status' => ModerationStatus::Unpublished,
                    'moderated_at' => now(),
                    'moderated_by' => Auth::id(),
                ])->save();

                $note = $data['resolution_note'] ?? 'Recipe unpublished.';
                $closed = 0;

                foreach (ContentFlag::query()->where('recipe_id', $recipe->id)->open()->get() as $flag) {
                    $flag->resolve(FlagStatus::Actioned, self::reviewer(), $note);
                    $closed++;
                }

                Notification::make()
                    ->success()
                    ->title('Recipe unpublished')
                    ->body($closed.' '.str('report')->plural($closed).' closed.')
                    ->send();
            });
    }

    public static function markActioned(): Action
    {
        return Action::make('markActioned')
            ->label('Mark actioned')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->authorize('resolve')
            ->schema([
                Textarea::make('resolution_note')
                    ->label('What you did')
                    ->placeholder('Edited the method, warned the author, and so on.')
                    ->rows(2)
                    ->required(),
            ])
            ->visible(fn (ContentFlag $record): bool => $record->status === FlagStatus::Open)
            ->action(function (ContentFlag $record, array $data): void {
                $record->resolve(FlagStatus::Actioned, self::reviewer(), $data['resolution_note']);

                Notification::make()->success()->title('Report closed')->send();
            });
    }

    public static function dismiss(): Action
    {
        return Action::make('dismiss')
            ->label('Dismiss')
            ->icon(Heroicon::OutlinedXCircle)
            ->color('gray')
            ->authorize('resolve')
            ->requiresConfirmation()
            ->modalHeading('Dismiss this report?')
            ->modalDescription('Nothing changes for the recipe. The report stays on file with your note.')
            ->schema([
                Textarea::make('resolution_note')
                    ->label('Why')
                    ->placeholder('Nothing wrong with the recipe.')
                    ->rows(2),
            ])
            ->visible(fn (ContentFlag $record): bool => $record->status === FlagStatus::Open)
            ->action(function (ContentFlag $record, array $data): void {
                $record->resolve(FlagStatus::Dismissed, self::reviewer(), $data['resolution_note'] ?? null);

                Notification::make()->success()->title('Report dismissed')->send();
            });
    }

    public static function dismissBulk(): BulkAction
    {
        return BulkAction::make('dismissSelected')
            ->label('Dismiss selected')
            ->icon(Heroicon::OutlinedXCircle)
            ->color('gray')
            ->authorize('resolveAny')
            ->requiresConfirmation()
            ->modalHeading('Dismiss the selected reports?')
            ->modalDescription('Reports already closed are skipped.')
            ->deselectRecordsAfterCompletion()
            ->action(fn (Collection $records) => self::dismissAll($records));
    }

    /**
     * Reports already decided are left as they are.
     *
     * @param  Collection<int, ContentFlag>  $records
     */
    private static function dismissAll(Collection $records): void
    {
        $open = $records->filter(fn (ContentFlag $flag): bool => $flag->status === FlagStatus::Open);

        foreach ($open as $flag) {
            $flag->resolve(FlagStatus::Dismissed, self::reviewer());
        }

        Notification::make()
            ->success()
            ->title($open->count().' '.str('report')->plural($open->count()).' dismissed')
            ->send();
    }

    private static function reviewer(): User
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
    }
}
