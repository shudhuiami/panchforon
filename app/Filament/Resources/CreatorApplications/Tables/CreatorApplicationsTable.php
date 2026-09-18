<?php

namespace App\Filament\Resources\CreatorApplications\Tables;

use App\Enums\CreatorApplicationStatus;
use App\Filament\Actions\CreatorApplicationActions;
use App\Filament\Resources\Users\UserResource;
use App\Models\CreatorApplication;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CreatorApplicationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('applicant.name')
                    ->label('Applicant')
                    ->searchable()
                    ->wrap()
                    ->weight('medium')
                    /**
                     * The pitch is the whole decision, so it rides with the
                     * name rather than hiding in a column phones drop.
                     */
                    ->description(fn (CreatorApplication $record): string => str($record->pitch)->limit(120)->toString())
                    ->url(fn (CreatorApplication $record): string => UserResource::getUrl('view', ['record' => $record->applicant])),

                TextColumn::make('youtube_channel_url')
                    ->label('Channel')
                    ->url(fn (CreatorApplication $record): ?string => $record->youtube_channel_url)
                    ->openUrlInNewTab()
                    ->limit(40)
                    ->placeholder('None given')
                    ->toggleable()
                    ->visibleFrom('xl'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (CreatorApplicationStatus $state): string => $state->label())
                    ->color(fn (CreatorApplicationStatus $state): string => $state->color())
                    ->icon(fn (CreatorApplicationStatus $state): string => $state->icon())
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Applied')
                    ->since()
                    ->sortable()
                    ->visibleFrom('md'),

                TextColumn::make('reviewer.name')
                    ->label('Decided by')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(CreatorApplicationStatus::cases())->mapWithKeys(fn (CreatorApplicationStatus $case): array => [$case->value => $case->label()])->all()),
            ])
            ->recordActions([
                ActionGroup::make([
                    CreatorApplicationActions::approve(),
                    CreatorApplicationActions::decline(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    CreatorApplicationActions::declineBulk(),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Nobody waiting')
            ->emptyStateDescription('When a member asks to become a creator their application lands here for a decision.');
    }
}
