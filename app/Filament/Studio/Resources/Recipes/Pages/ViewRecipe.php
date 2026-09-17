<?php

namespace App\Filament\Studio\Resources\Recipes\Pages;

use App\Filament\Studio\Resources\Recipes\RecipeResource;
use Filament\Resources\Pages\ViewRecord;

/**
 * Reading one of your own recipes.
 *
 * There is no edit or delete page behind this yet, and no header actions at
 * all: the record this page resolves is already restricted to the signed-in
 * creator by RecipeResource::getEloquentQuery(), so someone else's id never
 * reaches mount() and 404s instead.
 */
class ViewRecipe extends ViewRecord
{
    protected static string $resource = RecipeResource::class;

    /**
     * @return array<int, never>
     */
    protected function getHeaderActions(): array
    {
        return [];
    }
}
