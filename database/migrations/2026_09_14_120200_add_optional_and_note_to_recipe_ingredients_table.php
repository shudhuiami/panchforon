<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Optionality is a property of this recipe's use of an ingredient, not of
     * the ingredient: yogurt is optional in the chicken curry and required in
     * the roast.
     */
    public function up(): void
    {
        Schema::table('recipe_ingredients', function (Blueprint $table) {
            $table->boolean('is_optional')->default(false)->after('unit');
            /** The cook's aside: "3 medium, halved", "adjust to taste". */
            $table->string('note')->nullable()->after('is_optional');
        });
    }

    public function down(): void
    {
        Schema::table('recipe_ingredients', function (Blueprint $table) {
            $table->dropColumn(['is_optional', 'note']);
        });
    }
};
