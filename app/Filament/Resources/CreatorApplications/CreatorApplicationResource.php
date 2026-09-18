<?php

namespace App\Filament\Resources\CreatorApplications;

use App\Filament\Resources\CreatorApplications\Pages\ListCreatorApplications;
use App\Filament\Resources\CreatorApplications\Tables\CreatorApplicationsTable;
use App\Models\CreatorApplication;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CreatorApplicationResource extends Resource
{
    protected static ?string $model = CreatorApplication::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserPlus;

    protected static ?string $navigationLabel = 'Creator applications';

    protected static ?string $modelLabel = 'application';

    /**
     * Sits directly under the report queue, the other screen where an admin
     * works through things other people have submitted.
     */
    protected static ?int $navigationSort = 4;

    /**
     * Members apply for themselves; an admin who simply wants to hand somebody
     * the role does it on the user record, so there is nothing to create here.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return CreatorApplicationsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['applicant', 'reviewer']);
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = CreatorApplication::query()->pending()->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Applications waiting on a decision';
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCreatorApplications::route('/'),
        ];
    }
}
