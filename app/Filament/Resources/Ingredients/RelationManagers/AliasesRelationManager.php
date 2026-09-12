<?php

namespace App\Filament\Resources\Ingredients\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * The other names an ingredient goes by. Imports look these up before
 * creating a new entry, so an alias here stops the same ingredient arriving
 * twice under a different spelling.
 */
class AliasesRelationManager extends RelationManager
{
    protected static string $relationship = 'aliases';

    protected static ?string $title = 'Also called';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('alias')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255)
                ->helperText('Lower case. Imports match on this before creating a new ingredient.')
                ->dehydrateStateUsing(fn (string $state): string => mb_strtolower(trim($state))),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('alias')
            ->columns([
                TextColumn::make('alias')->searchable()->sortable(),
            ])
            ->defaultSort('alias')
            ->headerActions([
                CreateAction::make()->label('Add another name'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ])
            ->emptyStateHeading('No other names yet');
    }
}
