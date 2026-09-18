<?php

namespace App\Filament\Resources\CreatorApplications\Pages;

use App\Enums\CreatorApplicationStatus;
use App\Filament\Resources\CreatorApplications\CreatorApplicationResource;
use App\Models\CreatorApplication;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListCreatorApplications extends ListRecords
{
    protected static string $resource = CreatorApplicationResource::class;

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        /**
         * Pending leads, because it is the only tab with work in it; the rest
         * are the record of decisions already taken.
         */
        return [
            'pending' => Tab::make('Pending')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', CreatorApplicationStatus::Pending))
                ->badge(CreatorApplication::query()->pending()->count())
                ->badgeColor('warning'),

            'approved' => Tab::make('Approved')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', CreatorApplicationStatus::Approved))
                ->badge(CreatorApplication::query()->where('status', CreatorApplicationStatus::Approved)->count()),

            'declined' => Tab::make('Declined')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', CreatorApplicationStatus::Declined))
                ->badge(CreatorApplication::query()->where('status', CreatorApplicationStatus::Declined)->count()),

            'all' => Tab::make('All applications')
                ->badge(CreatorApplication::query()->count()),
        ];
    }
}
