<?php

namespace App\Filament\Resources\Users\Tables;

use App\Filament\Actions\UserModerationActions;
use App\Models\User;
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
                    ->description(fn (User $record): string => $record->email)
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
                    ->sortable()
                    ->visibleFrom('md'),

                /**
                 * Only suspension is badged. An active account renders the muted
                 * placeholder instead, so a coloured badge in this column always
                 * means something is wrong.
                 */
                TextColumn::make('suspended_at')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (): string => 'Suspended')
                    ->color('danger')
                    ->tooltip(fn (User $record): ?string => $record->suspension_reason)
                    ->placeholder('Active')
                    ->sortable(),

                TextColumn::make('recipes_count')
                    ->label('Recipes')
                    ->alignEnd()
                    ->sortable()
                    ->visibleFrom('lg'),

                TextColumn::make('ratings_count')
                    ->label('Ratings')
                    ->alignEnd()
                    ->sortable()
                    ->visibleFrom('lg'),

                TextColumn::make('created_at')
                    ->label('Joined')
                    ->date('j M Y')
                    ->sortable()
                    ->toggleable()
                    ->visibleFrom('xl'),
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
