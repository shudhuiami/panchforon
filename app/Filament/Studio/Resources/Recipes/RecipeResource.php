<?php

namespace App\Filament\Studio\Resources\Recipes;

use App\Filament\Studio\Resources\Recipes\Pages\CreateRecipe;
use App\Filament\Studio\Resources\Recipes\Pages\EditRecipe;
use App\Filament\Studio\Resources\Recipes\Pages\ListRecipes;
use App\Filament\Studio\Resources\Recipes\Pages\ViewRecipe;
use App\Filament\Studio\Resources\Recipes\Schemas\RecipeForm;
use App\Filament\Studio\Resources\Recipes\Schemas\RecipeInfolist;
use App\Filament\Studio\Resources\Recipes\Tables\RecipesTable;
use App\Models\Recipe;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * A creator's own recipes.
 *
 * Shares a class basename with the admin panel's RecipeResource on purpose:
 * Filament derives the slug and the labels from it, so this lands on
 * /studio/recipes with no overrides, and the namespace is what keeps the two
 * apart. They are otherwise unrelated — this one never shows a recipe its
 * viewer did not write.
 */
class RecipeResource extends Resource
{
    protected static ?string $model = Recipe::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 1;

    /**
     * The one place ownership is enforced.
     *
     * Every read in this resource runs through here: the list table, the tab
     * counts, and — because HasRoutes::getRecordRouteBindingEloquentQuery()
     * simply returns this query — route-model binding as well. So a URL
     * carrying another creator's recipe id resolves to no record at all and
     * 404s, rather than finding the record and then 403ing on a policy check.
     * That is the leak posture the public API already settled on in
     * Api\RecipeController::canViewRecipe(): refusing tells the caller the id
     * exists, and the id is the only thing they were fishing for.
     *
     * The owner id is read here, on every call, rather than resolved once and
     * remembered. A static resource class outlives a single request in a
     * queue worker or a test, and a memoised id would hand one creator's
     * recipes to whoever came next.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with('stat');

        $ownerId = Auth::id();

        /**
         * Nobody signed in means no recipes, never all of them. Passing null
         * straight to where() would compile to "user_id is null", which is
         * every imported recipe in the catalogue.
         */
        return $ownerId === null
            ? $query->whereRaw('0 = 1')
            : $query->where('recipes.user_id', $ownerId);
    }

    /**
     * There is deliberately no canCreate() override here any more. Writing is
     * what the studio is for, so the question goes to RecipePolicy::create(),
     * which allows any creator in good standing and refuses a suspended one.
     * Hard-coding true would hand a suspended creator a button the policy then
     * refuses on submit.
     */
    public static function form(Schema $schema): Schema
    {
        return RecipeForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RecipeInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RecipesTable::configure($table);
    }

    /**
     * Prep plus cook, which is the number a cook actually plans around. Null
     * when neither has been filled in — zero would claim the dish is instant.
     */
    public static function totalMinutes(Recipe $recipe): ?int
    {
        if ($recipe->prep_minutes === null && $recipe->cook_minutes === null) {
            return null;
        }

        return (int) $recipe->prep_minutes + (int) $recipe->cook_minutes;
    }

    /**
     * Deliberately no getNavigationBadge(): the admin resource's badge counts
     * the whole moderation queue, and putting that number in front of a
     * creator would tell them the size of everyone else's backlog. The counts
     * a creator wants are on the list tabs, already scoped to them.
     */
    public static function getPages(): array
    {
        return [
            'index' => ListRecipes::route('/'),
            'create' => CreateRecipe::route('/create'),
            'view' => ViewRecipe::route('/{record}'),
            'edit' => EditRecipe::route('/{record}/edit'),
        ];
    }
}
