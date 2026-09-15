<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * What an ingredient *is*, as opposed to how a recipe measures it.
     *
     * is_shoppable answers "would anyone put this on a shopping list?" — water
     * is real, matters to the method and scales with the dish, but nobody buys
     * it. The flag belongs here because the answer never changes from one
     * recipe to the next.
     *
     * default_dimension stays, demoted to a fallback for lines that carry no
     * unit at all; the row's own unit decides from now on.
     */
    public function up(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            $table->string('name_bn')->nullable()->after('canonical_name');
            $table->boolean('is_shoppable')->default(true)->after('default_dimension');
            /** The unit this ingredient is usually written in, for display and for new recipe rows. */
            $table->string('preferred_unit', 20)->nullable()->after('is_shoppable');
        });

        DB::table('ingredients')->whereIn('canonical_name', ['water', 'hot water', 'cold water'])
            ->update(['is_shoppable' => false]);
    }

    public function down(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            $table->dropColumn(['name_bn', 'is_shoppable', 'preferred_unit']);
        });
    }
};
