<?php

namespace App\Filament\Resources\Recipes\Pages;

use App\Filament\Actions\RecipeModerationActions;
use App\Filament\Resources\Recipes\RecipeResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewRecipe extends ViewRecord
{
    protected static string $resource = RecipeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            RecipeModerationActions::approve(),
            RecipeModerationActions::unpublish(),
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
