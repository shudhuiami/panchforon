<?php

namespace App\Filament\Studio\Resources\Recipes\Pages;

use App\Enums\ModerationStatus;
use App\Enums\RecipeSource;
use App\Filament\Studio\Resources\Recipes\RecipeResource;
use App\Models\Recipe;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

/**
 * Writing a new recipe.
 *
 * The form collects what a creator types; everything a recipe is on top of
 * that — who wrote it, where it came from, what it is called in a URL and
 * whether the site may show it — is stamped here rather than offered as a
 * field, because none of it is the creator's to choose.
 */
class CreateRecipe extends CreateRecord
{
    protected static string $resource = RecipeResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $author = Auth::user();

        /**
         * Read from the session, never from the form. A user_id field would be
         * a field a request could carry a different id in, and the whole
         * studio rests on a recipe belonging to whoever wrote it.
         */
        $data['user_id'] = Auth::id();
        $data['source'] = RecipeSource::User;

        $data['slug'] = Recipe::uniqueSlugFor((string) ($data['title'] ?? ''));

        $data['moderation_status'] = ModerationStatus::forAuthor(
            $author instanceof User ? $author : null,
            savesAsDraft: (bool) ($data['keep_as_draft'] ?? false),
        );

        /** A question the form asked, not a column the recipes table has. */
        unset($data['keep_as_draft']);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        $record = $this->getRecord();

        return ($record instanceof Recipe && $record->isDraft())
            ? 'Draft saved'
            : 'Recipe published';
    }
}
