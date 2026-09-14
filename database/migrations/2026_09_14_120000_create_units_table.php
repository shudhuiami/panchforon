<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The units a recipe line can be measured in, and what each one means.
     *
     * The symbol is the key: recipe_ingredients keeps writing the same short
     * string it always has, so nothing has to be migrated to ids, and a unit
     * an importer has never seen simply does not resolve rather than breaking
     * the import.
     *
     * The rows are written here rather than in a seeder because the merge
     * engine cannot do its job without them — a fresh install or a test
     * database needs them as much as production does.
     */
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->string('symbol', 20)->primary();
            $table->string('name', 60);
            $table->string('dimension', 10);
            /** How many of the dimension's canonical unit (g, ml, or one item) this unit is. */
            $table->decimal('factor_to_canonical', 12, 6);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->index('dimension');
        });

        DB::table('units')->insert($this->canonicalUnits());
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function canonicalUnits(): array
    {
        $now = now();

        $units = [
            // Mass, canonical gram
            ['g', 'gram', 'mass', 1, 10],
            ['kg', 'kilogram', 'mass', 1000, 20],
            ['oz', 'ounce', 'mass', 28.3495, 30],
            ['lb', 'pound', 'mass', 453.592, 40],

            // Volume, canonical millilitre
            ['ml', 'millilitre', 'volume', 1, 50],
            ['l', 'litre', 'volume', 1000, 60],
            ['tsp', 'teaspoon', 'volume', 4.92892, 70],
            ['tbsp', 'tablespoon', 'volume', 14.7868, 80],
            ['cup', 'cup', 'volume', 236.588, 90],
            ['floz', 'fluid ounce', 'volume', 29.5735, 100],

            // Count, canonical one item
            ['piece', 'piece', 'count', 1, 110],
            ['clove', 'clove', 'count', 1, 120],
            ['slice', 'slice', 'count', 1, 130],
            ['whole', 'whole', 'count', 1, 140],
        ];

        return array_map(fn (array $unit): array => [
            'symbol' => $unit[0],
            'name' => $unit[1],
            'dimension' => $unit[2],
            'factor_to_canonical' => $unit[3],
            'position' => $unit[4],
            'created_at' => $now,
            'updated_at' => $now,
        ], $units);
    }
};
