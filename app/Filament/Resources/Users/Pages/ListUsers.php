<?php

namespace App\Filament\Resources\Users\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge(User::query()->count()),

            /**
             * Creators earn a tab of their own now that the role is three-way:
             * they are the population an admin grants and revokes, and the
             * Admins tab no longer contains them. Only Suspended is coloured,
             * so a coloured badge on this page still means something is wrong.
             */
            'creators' => Tab::make('Creators')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('role', UserRole::Creator))
                ->badge(User::query()->where('role', UserRole::Creator)->count()),

            'admins' => Tab::make('Admins')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('role', UserRole::Admin))
                ->badge(User::query()->where('role', UserRole::Admin)->count()),

            'suspended' => Tab::make('Suspended')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('suspended_at'))
                ->badge(User::query()->suspended()->count())
                ->badgeColor('danger'),
        ];
    }
}
