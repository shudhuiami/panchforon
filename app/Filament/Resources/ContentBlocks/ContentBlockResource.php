<?php

namespace App\Filament\Resources\ContentBlocks;

use App\Filament\Resources\ContentBlocks\Pages\EditContentBlock;
use App\Filament\Resources\ContentBlocks\Pages\ListContentBlocks;
use App\Filament\Resources\ContentBlocks\Schemas\ContentBlockForm;
use App\Filament\Resources\ContentBlocks\Tables\ContentBlocksTable;
use App\Models\ContentBlock;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ContentBlockResource extends Resource
{
    protected static ?string $model = ContentBlock::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPencilSquare;

    protected static ?string $navigationLabel = 'Storefront wording';

    protected static ?string $modelLabel = 'piece of wording';

    protected static ?string $recordTitleAttribute = 'label';

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 2;

    /**
     * The set of editable strings is defined in code, so this screen edits
     * them and nothing else.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return ContentBlockForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContentBlocksTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContentBlocks::route('/'),
            'edit' => EditContentBlock::route('/{record}/edit'),
        ];
    }
}
