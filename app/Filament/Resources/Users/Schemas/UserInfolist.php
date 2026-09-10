<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    /**
     * Credentials are never listed here. The model hides password and
     * remember_token from serialization, and no entry below reads them.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Account')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('email')
                            ->label('Email address')
                            ->copyable(),
                        TextEntry::make('created_at')
                            ->label('Joined')
                            ->dateTime('j M Y, H:i'),
                    ]),

                Section::make('Access')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('is_admin')
                            ->label('Role')
                            ->badge()
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Administrator' : 'Member')
                            ->color(fn (bool $state): string => $state ? 'primary' : 'gray'),

                        TextEntry::make('suspended_at')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn ($state): string => $state ? 'Suspended' : 'Active')
                            ->color(fn ($state): string => $state ? 'danger' : 'success')
                            ->default(''),

                        TextEntry::make('email_verified_at')
                            ->label('Email verified')
                            ->dateTime('j M Y')
                            ->placeholder('Not verified'),

                        TextEntry::make('suspension_reason')
                            ->label('Suspension reason')
                            ->columnSpanFull()
                            ->visible(fn (User $record): bool => $record->isSuspended()),
                    ]),

                Section::make('Contributions')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('recipes_count')
                            ->label('Recipes submitted')
                            ->state(fn (User $record): int => $record->recipes()->count()),

                        TextEntry::make('ratings_count')
                            ->label('Ratings left')
                            ->state(fn (User $record): int => $record->ratings()->count()),
                    ]),
            ]);
    }
}
