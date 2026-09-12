<?php

namespace App\Filament\Resources\ContentBlocks\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ContentBlocksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')
                    ->label('Where it appears')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('body')
                    ->label('Wording')
                    ->wrap()
                    ->limit(120)
                    ->searchable(),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->since()
                    ->sortable()
                    ->visibleFrom('md'),
            ])
            ->defaultSort('label')
            ->recordActions([
                EditAction::make(),
            ])
            ->paginated(false)
            ->emptyStateHeading('Nothing to edit yet');
    }
}
