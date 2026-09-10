<?php

namespace App\Filament\Resources\Users\Tables;

use App\Filament\Actions\UserModerationActions;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record): string => $record->email)
                    ->weight('medium'),

                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('is_admin')
                    ->label('Role')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Admin' : 'Member')
                    ->color(fn (bool $state): string => $state ? 'primary' : 'gray')
                    ->sortable(),

                TextColumn::make('suspended_at')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state ? 'Suspended' : 'Active')
                    ->color(fn ($state): string => $state ? 'danger' : 'success')
                    ->tooltip(fn ($record): ?string => $record->suspension_reason)
                    ->default('')
                    ->sortable(),

                TextColumn::make('recipes_count')
                    ->label('Recipes')
                    ->alignEnd()
                    ->sortable(),

                TextColumn::make('ratings_count')
                    ->label('Ratings')
                    ->alignEnd()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Joined')
                    ->date('j M Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('is_admin')
                    ->label('Role')
                    ->placeholder('Everyone')
                    ->trueLabel('Admins only')
                    ->falseLabel('Members only'),

                TernaryFilter::make('suspended')
                    ->label('Status')
                    ->placeholder('Everyone')
                    ->trueLabel('Suspended only')
                    ->falseLabel('Active only')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('suspended_at'),
                        false: fn (Builder $query) => $query->whereNull('suspended_at'),
                        blank: fn (Builder $query) => $query,
                    ),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    UserModerationActions::toggleAdmin(),
                    UserModerationActions::suspend(),
                    UserModerationActions::liftSuspension(),
                ]),
            ])
            ->toolbarActions([])
            ->emptyStateHeading('No users yet')
            ->emptyStateDescription('People who register on Panchforon will appear here.');
    }
}
