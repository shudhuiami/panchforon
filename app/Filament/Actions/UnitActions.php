<?php

namespace App\Filament\Actions;

use App\Models\Ingredient;
use App\Models\RecipeIngredient;
use App\Models\Unit;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;

/**
 * The destructive edge of the units table.
 *
 * A recipe line stores its unit as a symbol, not as a foreign key, so nothing
 * in the database stops a unit being deleted out from under the lines written
 * in it. They would keep their text and quietly stop resolving: no dimension,
 * no conversion, and a shopping list that no longer adds them up. This is the
 * guard that would otherwise be a foreign key.
 */
class UnitActions
{
    public static function delete(): DeleteAction
    {
        return DeleteAction::make()
            ->modalHeading(fn (Unit $record): string => "Delete the \"{$record->symbol}\" unit")
            ->modalDescription('Recipe lines keep the symbol they were written in. Once the unit is gone they no longer resolve, so the shopping list stops converting and merging them.')
            ->before(function (Unit $record, DeleteAction $action): void {
                $blockers = self::usageSummary($record);

                if ($blockers === null) {
                    return;
                }

                Notification::make()
                    ->danger()
                    ->title("\"{$record->symbol}\" is still in use")
                    ->body($blockers.' Move those onto another unit first — this one has been left alone.')
                    ->persistent()
                    ->send();

                $action->cancel();
            });
    }

    /**
     * How many recipe lines are written in this unit.
     */
    public static function recipeLinesUsing(Unit $unit): int
    {
        return RecipeIngredient::query()->where('unit', $unit->symbol)->count();
    }

    /**
     * How many ingredients name this unit as the one they are usually
     * written in.
     */
    public static function ingredientsPreferring(Unit $unit): int
    {
        return Ingredient::query()->where('preferred_unit', $unit->symbol)->count();
    }

    /**
     * A sentence naming what still depends on this unit, or null when nothing
     * does and it is safe to remove.
     */
    public static function usageSummary(Unit $unit): ?string
    {
        $lines = self::recipeLinesUsing($unit);
        $ingredients = self::ingredientsPreferring($unit);

        $parts = [];

        if ($lines > 0) {
            $parts[] = $lines.' '.str('recipe line')->plural($lines);
        }

        if ($ingredients > 0) {
            $parts[] = $ingredients.' '.str('ingredient')->plural($ingredients).' written in it by default';
        }

        if ($parts === []) {
            return null;
        }

        $verb = ($lines + $ingredients) === 1 ? 'depends' : 'depend';

        return ucfirst(implode(' and ', $parts)).' '.$verb.' on it.';
    }
}
