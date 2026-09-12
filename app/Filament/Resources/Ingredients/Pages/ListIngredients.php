<?php

namespace App\Filament\Resources\Ingredients\Pages;

use App\Filament\Resources\Ingredients\IngredientResource;
use App\Models\Ingredient;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListIngredients extends ListRecords
{
    protected static string $resource = IngredientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        /**
         * "Unused" is the one worth having: every import creates an entry for
         * any name it cannot resolve, so the tail of the dictionary is where
         * duplicates and typos collect.
         */
        return [
            'all' => Tab::make('All ingredients')
                ->badge(Ingredient::query()->count()),

            'unused' => Tab::make('Unused')
                ->modifyQueryUsing(fn (Builder $query) => $query->doesntHave('recipeIngredients'))
                ->badge(Ingredient::query()->doesntHave('recipeIngredients')->count())
                ->badgeColor('warning'),
        ];
    }
}
