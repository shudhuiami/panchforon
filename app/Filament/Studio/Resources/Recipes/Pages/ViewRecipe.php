<?php

namespace App\Filament\Studio\Resources\Recipes\Pages;

use App\Filament\Studio\Resources\Recipes\RecipeResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

/**
 * Reading one of your own recipes.
 *
 * The record this page resolves is already restricted to the signed-in creator
 * by RecipeResource::getEloquentQuery(), so someone else's id never reaches
 * mount() and 404s instead. Editing is offered from here; deleting is not,
 * because a delete on a page about one recipe reads as a slip waiting to
 * happen — it lives on the edit screen, one step further in.
 */
class ViewRecipe extends ViewRecord
{
    protected static string $resource = RecipeResource::class;

    /**
     * @return array<int, mixed>
     */
    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
