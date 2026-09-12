<?php

namespace App\Filament\Resources\ContentFlags\Pages;

use App\Enums\FlagStatus;
use App\Filament\Resources\ContentFlags\ContentFlagResource;
use App\Models\ContentFlag;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListContentFlags extends ListRecords
{
    protected static string $resource = ContentFlagResource::class;

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        /**
         * Open reports lead, because everything else on this screen is
         * already decided and only kept for the record.
         */
        return [
            'open' => Tab::make('Open')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', FlagStatus::Open))
                ->badge(ContentFlag::query()->open()->count())
                ->badgeColor('danger'),

            'actioned' => Tab::make('Actioned')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', FlagStatus::Actioned))
                ->badge(ContentFlag::query()->where('status', FlagStatus::Actioned)->count()),

            'dismissed' => Tab::make('Dismissed')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', FlagStatus::Dismissed))
                ->badge(ContentFlag::query()->where('status', FlagStatus::Dismissed)->count()),

            'all' => Tab::make('All reports')
                ->badge(ContentFlag::query()->count()),
        ];
    }
}
