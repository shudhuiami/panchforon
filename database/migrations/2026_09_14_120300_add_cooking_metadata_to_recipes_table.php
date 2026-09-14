<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Times are integers in minutes, never strings: they are meant to be
     * filtered, summed into a plan and compared, none of which "40–50 mins"
     * supports. Spice level is nullable because plenty of dishes have nothing
     * meaningful to say about it.
     */
    public function up(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->string('name_bn')->nullable()->after('title');
            $table->unsignedSmallInteger('prep_minutes')->nullable()->after('servings');
            $table->unsignedSmallInteger('cook_minutes')->nullable()->after('prep_minutes');
            $table->string('spice_level', 10)->nullable()->after('cook_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropColumn(['name_bn', 'prep_minutes', 'cook_minutes', 'spice_level']);
        });
    }
};
