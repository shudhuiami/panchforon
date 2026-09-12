<?php

namespace App\Filament\Resources\Pages\Tables;

use App\Models\Page;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->description(fn (Page $record): string => '/p/'.$record->slug),

                IconColumn::make('is_published')
                    ->label('Published')
                    ->boolean()
                    ->sortable(),

                IconColumn::make('show_in_footer')
                    ->label('In footer')
                    ->boolean()
                    ->sortable()
                    ->visibleFrom('md'),

                TextColumn::make('position')
                    ->label('Order')
                    ->alignEnd()
                    ->sortable()
                    ->visibleFrom('lg'),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->since()
                    ->sortable()
                    ->visibleFrom('md'),
            ])
            ->defaultSort('position')
            ->filters([
                TernaryFilter::make('is_published')->label('Published'),
                TernaryFilter::make('show_in_footer')->label('In the footer'),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No pages yet')
            ->emptyStateDescription('About, privacy and anything else you want to write lives here.');
    }
}
