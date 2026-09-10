<?php

namespace App\Filament\Actions;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

/**
 * Record actions for the user resource.
 *
 * Every action refuses to act on the signed-in admin's own account, so nobody
 * can revoke their own panel access or suspend themselves and be locked out.
 */
class UserModerationActions
{
    public static function toggleAdmin(): Action
    {
        return Action::make('toggleAdmin')
            ->label(fn (User $record): string => $record->is_admin ? 'Revoke admin' : 'Make admin')
            ->icon(fn (User $record): Heroicon => $record->is_admin ? Heroicon::OutlinedShieldExclamation : Heroicon::OutlinedShieldCheck)
            ->color(fn (User $record): string => $record->is_admin ? 'gray' : 'primary')
            ->authorize('moderate')
            ->requiresConfirmation()
            ->modalHeading(fn (User $record): string => $record->is_admin
                ? "Revoke admin access for {$record->name}?"
                : "Grant admin access to {$record->name}?")
            ->modalDescription(fn (User $record): string => $record->is_admin
                ? 'They will lose access to this panel immediately.'
                : 'They will be able to reach this panel and moderate content.')
            ->visible(fn (User $record): bool => ! self::isSelf($record))
            ->action(function (User $record): void {
                $record->forceFill(['is_admin' => ! $record->is_admin])->save();

                Notification::make()
                    ->success()
                    ->title($record->is_admin ? 'Admin access granted' : 'Admin access revoked')
                    ->body("{$record->name} is now ".($record->is_admin ? 'an administrator.' : 'a regular member.'))
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
