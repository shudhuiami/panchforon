<?php

namespace App\Filament\Resources\Units;

use App\Enums\UnitDimension;
use App\Filament\Resources\Units\Pages\CreateUnit;
use App\Filament\Resources\Units\Pages\EditUnit;
use App\Filament\Resources\Units\Pages\ListUnits;
use App\Filament\Resources\Units\Schemas\UnitForm;
use App\Filament\Resources\Units\Tables\UnitsTable;
use App\Models\RecipeIngredient;
use App\Models\Unit;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * The units table, which is reference data rather than content: the shopping
 * list converts every recipe line through the factor stored here before it
 * adds anything up, so a row edited on this screen changes totals everywhere.
 */
class UnitResource extends Resource
{
    protected static ?string $model = Unit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static ?string $recordTitleAttribute = 'symbol';

    protected static string|\UnitEnum|null $navigationGroup = 'Data';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'unit';

    public static function form(Schema $schema): Schema
    {
        return UnitForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UnitsTable::configure($table);
    }

    /**
     * Counts how many recipe lines are written in each unit, so the table can
     * show what is at stake before anybody edits or deletes a row. There is no
     * relation to count through: a line stores the symbol as a string.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->select('units.*')
            ->addSelect([
                'recipe_lines_count' => RecipeIngredient::query()
                    ->selectRaw('count(*)')
                    ->whereColumn('recipe_ingredients.unit', 'units.symbol'),
            ]);
    }

    /**
     * Every dimension, including None, which only ingredients use as a
     * fallback for lines carrying no unit at all.
     *
     * @return array<string, string>
     */
    public static function dimensionOptions(): array
    {
        $options = [];

        foreach (UnitDimension::cases() as $dimension) {
            $options[$dimension->value] = self::dimensionLabel($dimension);
        }

        return $options;
    }

    /**
     * The dimensions a unit itself can belong to. A unit with no dimension
     * would be a unit nothing could be converted through.
     *
     * @return array<string, string>
     */
    public static function measuredDimensionOptions(): array
    {
        $options = self::dimensionOptions();

        unset($options[UnitDimension::None->value]);

        return $options;
    }

    /**
     * "mass" stays the stored value everywhere — the enum, the ingredients
     * table and the merge engine all speak it. Only the label reads "Weight",
     * which is the word a cook would use.
     */
    public static function dimensionLabel(UnitDimension $dimension): string
    {
        return match ($dimension) {
            UnitDimension::Mass => 'Weight',
            UnitDimension::Volume => 'Volume',
            UnitDimension::Count => 'Count',
            UnitDimension::None => 'None',
        };
    }

    /**
     * The unit a dimension is converted to before two lines are added up.
     */
    public static function canonicalSymbol(UnitDimension $dimension): string
    {
        return match ($dimension) {
            UnitDimension::Mass => 'g',
            UnitDimension::Volume => 'ml',
            UnitDimension::Count => 'items',
            UnitDimension::None => '',
        };
    }

    /**
     * Every unit, grouped by dimension, for the pickers on other screens.
     *
     * @return array<string, array<string, string>>
     */
    public static function unitOptionsByDimension(): array
    {
        $options = [];

        foreach (Unit::query()->orderBy('position')->get() as $unit) {
            $options[self::dimensionLabel($unit->dimension)][$unit->symbol] = "{$unit->symbol} — {$unit->name}";
        }

        return $options;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUnits::route('/'),
            'create' => CreateUnit::route('/create'),
            'edit' => EditUnit::route('/{record}/edit'),
        ];
    }
}
