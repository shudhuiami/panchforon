<?php

namespace App\Filament\Resources\ContentFlags;

use App\Filament\Resources\ContentFlags\Pages\ListContentFlags;
use App\Filament\Resources\ContentFlags\Tables\ContentFlagsTable;
use App\Models\ContentFlag;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ContentFlagResource extends Resource
{
    protected static ?string $model = ContentFlag::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static ?string $navigationLabel = 'Reported recipes';

    protected static ?string $modelLabel = 'report';

    protected static ?int $navigationSort = 3;

    /**
     * Reports come from the community through the API; there is nothing to
     * create or edit here, only to decide.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return ContentFlagsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['recipe', 'reporter', 'reviewer']);
    }

    public static function getNavigationBadge(): ?string
    {
        $open = ContentFlag::query()->open()->count();

        return $open > 0 ? (string) $open : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Open reports';
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContentFlags::route('/'),
        ];
    }
}
