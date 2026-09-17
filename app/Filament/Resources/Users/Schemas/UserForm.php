<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class UserForm
{
    /**
     * Credentials are intentionally absent. Filament fills form fields from the
     * model, so declaring a password field here would put the bcrypt hash in
     * the rendered HTML. Admins manage roles and suspension, not passwords.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Account')
                    ->description('Profile details shown to other people on the site.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email address')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                    ]),

                Section::make('Access')
                    ->description('The role decides what someone may do: creators publish without review, administrators do that and reach this panel. Suspension is reversible and blocks login, existing API tokens, and panel access.')
                    ->schema([
                        Select::make('role')
                            ->label('Role')
                            ->native(false)
                            ->required()
                            ->options(collect(UserRole::cases())
                                ->mapWithKeys(fn (UserRole $role): array => [$role->value => $role->label()])
                                ->all())
                            ->helperText('Members submit recipes through the moderation queue. Creators skip it and get the studio. Administrators get this panel as well.')
                            /**
                             * The self-lockout guard, unchanged in substance by
                             * the move from a toggle to a select: on your own
                             * record the field renders read-only and is dropped
                             * before save, so whatever arrives in the request
                             * cannot demote you.
                             */
                            ->disabled(fn (?User $record): bool => self::isCurrentUser($record))
                            ->dehydrated(fn (?User $record): bool => ! self::isCurrentUser($record))
                            ->hintIcon(
                                fn (?User $record): ?string => self::isCurrentUser($record) ? 'heroicon-m-information-circle' : null,
                                tooltip: 'You cannot change your own role.',
                            ),
                    ]),
            ]);
    }

    /**
     * Guards against an admin locking themselves out of the panel.
     */
    private static function isCurrentUser(?User $record): bool
    {
        return $record !== null && $record->getKey() === Auth::id();
    }
}
