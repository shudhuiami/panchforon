<?php

namespace App\Filament\Actions;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

/**
 * Record actions for the user resource.
 *
 * Every action refuses to act on the signed-in admin's own account, so nobody
 * can demote themselves out of this panel or suspend themselves and be locked
 * out.
 */
class UserModerationActions
{
    /**
     * Set someone's role outright.
     *
     * A toggle could only ever say admin or not, which is no longer what the
     * role is. Picking from the three cases is also how an admin grants the
     * creator role by hand, the other route being a creator application.
     */
    public static function changeRole(): Action
    {
        return Action::make('changeRole')
            ->label('Change role')
            ->icon(Heroicon::OutlinedShieldCheck)
            ->color('primary')
            ->authorize('moderate')
            ->modalHeading(fn (User $record): string => "Change the role for {$record->name}")
            ->modalDescription('Creators publish recipes without review and reach the studio. Administrators do that as well as reaching this panel.')
            ->modalSubmitActionLabel('Change role')
            ->visible(fn (User $record): bool => ! self::isSelf($record))
            ->fillForm(fn (User $record): array => ['role' => $record->role->value])
            ->schema([
                Select::make('role')
                    ->label('Role')
                    ->native(false)
                    ->required()
                    ->options(collect(UserRole::cases())
                        ->mapWithKeys(fn (UserRole $role): array => [$role->value => $role->label()])
                        ->all()),
            ])
            ->action(function (User $record, array $data): void {
                $role = UserRole::from($data['role']);

                $record->forceFill(['role' => $role])->save();

                Notification::make()
                    ->success()
                    ->title('Role changed')
                    ->body("{$record->name} now has the {$role->label()} role.")
                    ->send();
            });
    }

    public static function suspend(): Action
    {
        return Action::make('suspend')
            ->label('Suspend')
            ->icon(Heroicon::OutlinedNoSymbol)
            ->color('danger')
            ->authorize('moderate')
            ->visible(fn (User $record): bool => ! $record->isSuspended() && ! self::isSelf($record))
            ->modalHeading(fn (User $record): string => "Suspend {$record->name}?")
            ->modalDescription('They will be signed out of the API, blocked from logging back in, and locked out of this panel. This is reversible.')
            ->modalSubmitActionLabel('Suspend account')
            ->schema([
                Textarea::make('suspension_reason')
                    ->label('Reason')
                    ->placeholder('Recorded for other admins. Shown to the user when they try to sign in.')
                    ->required()
                    ->maxLength(255)
                    ->rows(3),
            ])
            ->action(function (User $record, array $data): void {
                $record->forceFill([
                    'suspended_at' => now(),
                    'suspension_reason' => $data['suspension_reason'],
                ])->save();

                /**
                 * Revoking tokens is belt-and-braces: EnsureUserIsNotSuspended
                 * already rejects them, but leaving live tokens on a suspended
                 * account serves no purpose.
                 */
                $record->tokens()->delete();

                Notification::make()
                    ->success()
                    ->title('Account suspended')
                    ->body("{$record->name} can no longer sign in. Their API tokens were revoked.")
                    ->send();
            });
    }

    public static function liftSuspension(): Action
    {
        return Action::make('liftSuspension')
            ->label('Lift suspension')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->authorize('moderate')
            ->requiresConfirmation()
            ->modalHeading(fn (User $record): string => "Restore access for {$record->name}?")
            ->modalDescription('They will be able to sign in again. They will need to log in afresh, since their previous tokens were revoked.')
            ->visible(fn (User $record): bool => $record->isSuspended())
            ->action(function (User $record): void {
                $record->forceFill([
                    'suspended_at' => null,
                    'suspension_reason' => null,
                ])->save();

                Notification::make()
                    ->success()
                    ->title('Suspension lifted')
                    ->body("{$record->name} can sign in again.")
                    ->send();
            });
    }

    private static function isSelf(User $record): bool
    {
        return $record->getKey() === Auth::id();
    }
}
