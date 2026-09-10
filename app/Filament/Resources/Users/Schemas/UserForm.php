<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
                    ->description('Administrators can reach this panel. Suspension is reversible and blocks login, existing API tokens, and panel access.')
                    ->schema([
                        Toggle::make('is_admin')
                            ->label('Administrator')
                            ->helperText('Grants access to the Panchforon admin panel.')
                            ->disabled(fn (?User $record): bool => self::isCurrentUser($record))
                            ->dehydrated(fn (?User $record): bool => ! self::isCurrentUser($record))
                            ->hintIcon(
                                fn (?User $record): ?string => self::isCurrentUser($record) ? 'heroicon-m-information-circle' : null,
                                tooltip: 'You cannot change your own admin status.',
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
