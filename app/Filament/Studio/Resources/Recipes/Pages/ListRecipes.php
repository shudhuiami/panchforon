<?php

namespace App\Filament\Studio\Resources\Recipes\Pages;

use App\Enums\ModerationStatus;
use App\Filament\Studio\Resources\Recipes\RecipeResource;
use App\Models\Recipe;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListRecipes extends ListRecords
{
    protected static string $resource = RecipeResource::class;

    /**
     * No header actions. Importing and renaming taxonomies are catalogue-wide
     * jobs and belong to the admin panel; writing a recipe arrives with the
     * create screen in the next chunk.
     *
     * @return array<int, never>
     */
    protected function getHeaderActions(): array
    {
        return [];
    }

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        /**
         * "Everything" is first, and therefore the default, so a creator lands
         * on all of their own work rather than a slice of it. A recipe still
         * awaiting review has no tab of its own — creators publish without
         * review, so that state only exists for submissions made before the
         * role was granted — but it is on this first tab with its status
         * showing.
         */
        return [
            'all' => Tab::make('Everything')
                ->badge($this->countOwn()),

            'drafts' => Tab::make('Drafts')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('moderation_status', ModerationStatus::Draft))
                ->badge($this->countOwn(ModerationStatus::Draft))
                ->badgeColor('info'),

            'live' => Tab::make('Live')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('moderation_status', ModerationStatus::Approved))
                ->badge($this->countOwn(ModerationStatus::Approved))
                ->badgeColor('success'),

            'unpublished' => Tab::make('Taken down')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('moderation_status', ModerationStatus::Unpublished))
                ->badge($this->countOwn(ModerationStatus::Unpublished))
                ->badgeColor('gray'),
        ];
    }

    /**
     * Tab badges count the signed-in creator's recipes and nobody else's.
     *
     * The table's own rows come from RecipeResource::getEloquentQuery(), but a
     * badge is a separate query, so it repeats the ownership filter rather
     * than inheriting it. A count is small, and leaking one is still a leak.
     */
    protected function countOwn(?ModerationStatus $status = null): int
    {
        $ownerId = Auth::id();

        if ($ownerId === null) {
            return 0;
        }

        $query = Recipe::query()->where('recipes.user_id', $ownerId);

        if ($status !== null) {
            $query->where('recipes.moderation_status', $status);
        }

        return $query->count();
    }
}
