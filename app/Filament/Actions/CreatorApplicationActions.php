<?php

namespace App\Filament\Actions;

use App\Enums\CreatorApplicationStatus;
use App\Enums\UserRole;
use App\Models\CreatorApplication;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Deciding who gets trusted with the catalogue.
 *
 * An admin has two ways out: approve, which grants the creator role and closes
 * the application, or decline, which closes it and leaves the account alone.
 * The role change lives here rather than on the model because the record of a
 * decision and the consequence of it are separate concerns.
 */
class CreatorApplicationActions
{
    public static function approve(): Action
    {
        return Action::make('approve')
            ->label('Approve')
            ->icon(Heroicon::OutlinedCheckBadge)
            ->color('success')
            ->authorize('decide')
            ->requiresConfirmation()
            ->modalHeading(fn (CreatorApplication $record): string => 'Make '.$record->applicant->name.' a creator?')
            ->modalDescription('Their recipes go straight onto the site from then on, and the studio opens up to them.')
            ->schema([
                Textarea::make('review_note')
                    ->label('Note for the record')
                    ->placeholder('What convinced you.')
                    ->rows(2),
            ])
            ->visible(fn (CreatorApplication $record): bool => $record->status === CreatorApplicationStatus::Pending)
            ->action(function (CreatorApplication $record, array $data): void {
                DB::transaction(function () use ($record, $data): void {
                    $applicant = $record->applicant;

                    /**
                     * An admin who applied is already above the creator role,
                     * so approving them records the decision and stops there.
                     * Approving somebody must never cost them permissions.
                     */
                    if (! $applicant->isAdmin()) {
                        $applicant->update(['role' => UserRole::Creator]);
                    }

                    $record->decide(CreatorApplicationStatus::Approved, self::reviewer(), $data['review_note'] ?? null);
                });

                Notification::make()->success()->title('Application approved')->send();
            });
    }

    public static function decline(): Action
    {
        return Action::make('decline')
            ->label('Decline')
            ->icon(Heroicon::OutlinedXCircle)
            ->color('gray')
            ->authorize('decide')
            ->schema([
                Textarea::make('review_note')
                    ->label('Why')
                    ->placeholder('The applicant reads this, so say what would make the next attempt stronger.')
                    ->rows(2)
                    ->required(),
            ])
            ->visible(fn (CreatorApplication $record): bool => $record->status === CreatorApplicationStatus::Pending)
            ->action(function (CreatorApplication $record, array $data): void {
                $record->decide(CreatorApplicationStatus::Declined, self::reviewer(), $data['review_note']);

                Notification::make()->success()->title('Application declined')->send();
            });
    }

    /**
     * Clearing out a run of obvious non-starters in one pass.
     *
     * There is no note here, unlike the single decline: one sentence cannot
     * honestly speak to a batch of different applications, and nothing stops
     * an admin declining individually where a reason is worth writing.
     */
    public static function declineBulk(): BulkAction
    {
        return BulkAction::make('declineSelected')
            ->label('Decline selected')
            ->icon(Heroicon::OutlinedXCircle)
            ->color('gray')
            ->authorize('decideAny')
            ->requiresConfirmation()
            ->modalHeading('Decline the selected applications?')
            ->modalDescription('Applications already decided are skipped, and nobody loses a role they already hold.')
            ->deselectRecordsAfterCompletion()
            ->action(fn (Collection $records) => self::declineAll($records));
    }

    /**
     * Applications already decided are left as they are.
     *
     * @param  Collection<int, CreatorApplication>  $records
     */
    private static function declineAll(Collection $records): void
    {
        $pending = $records->filter(fn (CreatorApplication $application): bool => $application->status === CreatorApplicationStatus::Pending);

        foreach ($pending as $application) {
            $application->decide(CreatorApplicationStatus::Declined, self::reviewer());
        }

        Notification::make()
            ->success()
            ->title($pending->count().' '.str('application')->plural($pending->count()).' declined')
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
