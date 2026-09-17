<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\UserRole;
use App\Filament\Actions\UserModerationActions;
use App\Models\User;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
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

                TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->formatStateUsing(fn (UserRole $state): string => $state->label())
                    ->color(fn (UserRole $state): string => $state->color())
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
                SelectFilter::make('role')
                    ->label('Role')
                    ->options(collect(UserRole::cases())
                        ->mapWithKeys(fn (UserRole $role): array => [$role->value => $role->label()])
                        ->all()),

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
                    UserModerationActions::changeRole(),
                    UserModerationActions::suspend(),
                    UserModerationActions::liftSuspension(),
                ]),
            ])
            ->toolbarActions([])
            ->emptyStateHeading('No users yet')
            ->emptyStateDescription('People who register on Panchforon will appear here.');
    }
}
