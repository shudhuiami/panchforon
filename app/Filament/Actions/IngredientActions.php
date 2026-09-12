<?php

namespace App\Filament\Actions;

use App\Models\Ingredient;
use App\Services\IngredientMerger;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class IngredientActions
{
    /**
     * Fold a duplicate into the ingredient that should have been used.
     */
    public static function merge(): Action
    {
        return Action::make('merge')
            ->label('Merge into…')
            ->icon(Heroicon::OutlinedArrowsPointingIn)
            ->color('warning')
            ->authorize('merge')
            ->modalHeading(fn (Ingredient $record): string => "Merge \"{$record->canonical_name}\" into another ingredient")
            ->modalDescription('Every recipe line moves across, this name is kept as an alias so imports resolve it, and this entry is removed.')
            ->modalSubmitActionLabel('Merge')
            ->schema([
                Select::make('into')
                    ->label('Keep this one')
                    ->required()
                    ->searchable()
                    ->options(fn (Ingredient $record): array => Ingredient::query()
                        ->whereKeyNot($record->getKey())
                        ->orderBy('canonical_name')
                        ->limit(50)
                        ->pluck('canonical_name', 'id')
                        ->all())
                    ->getSearchResultsUsing(fn (string $search, Ingredient $record): array => Ingredient::query()
                        ->whereKeyNot($record->getKey())
                        ->where('canonical_name', 'like', "%{$search}%")
                        ->orderBy('canonical_name')
                        ->limit(50)
                        ->pluck('canonical_name', 'id')
                        ->all()),
            ])
            ->action(function (Ingredient $record, array $data): void {
                $into = Ingredient::find($data['into']);

                if ($into === null) {
                    Notification::make()->danger()->title('That ingredient no longer exists')->send();

                    return;
                }

                $retired = $record->canonical_name;
                $moved = app(IngredientMerger::class)->merge($record, $into);

                Notification::make()
                    ->success()
                    ->title('Merged')
                    ->body("\"{$retired}\" now resolves to \"{$into->canonical_name}\". {$moved} ".str('recipe line')->plural($moved).' moved.')
                    ->send();
            });
    }

    /**
     * Ingredients nothing uses, which imports leave behind.
     *
     * @param  Builder<Ingredient>  $query
     */
    public static function scopeUnused(Builder $query): void
    {
        $query->doesntHave('recipeIngredients');
    }
}
