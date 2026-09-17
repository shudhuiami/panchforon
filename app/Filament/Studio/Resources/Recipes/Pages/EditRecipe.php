<?php

namespace App\Filament\Studio\Resources\Recipes\Pages;

use App\Filament\Studio\Resources\Recipes\RecipeResource;
use App\Models\Recipe;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

/**
 * Editing one of your own recipes.
 *
 * The record this page resolves is already restricted to the signed-in creator
 * by RecipeResource::getEloquentQuery(), so another creator's id 404s before
 * mount() runs; RecipePolicy::update() then checks ownership again on the way
 * in, and RecipePolicy::delete() on the action below.
 *
 * Nothing here changes the moderation status. An edit is a change of content,
 * and a live recipe stays live through one — the same promise the API's update
 * endpoint makes, in the same words.
 */
class EditRecipe extends EditRecord
{
    protected static string $resource = RecipeResource::class;

    /**
     * @return array<int, mixed>
     */
    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    /**
     * The draft toggle is a create-time question and is hidden here, but a
     * hidden field that somehow arrived would be a status change through a
     * content form. Dropped rather than trusted.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['keep_as_draft'], $data['user_id'], $data['source'], $data['moderation_status']);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getSavedNotificationTitle(): ?string
    {
        $record = $this->getRecord();

        return ($record instanceof Recipe && $record->isDraft())
            ? 'Draft saved'
            : 'Recipe updated';
    }
}
