<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meal_plan_items', function (Blueprint $table) {
            $table->date('planned_for')->nullable()->after('recipe_id');
            $table->string('meal_slot', 20)->default('dinner')->after('planned_for');
        });

        $this->backfillExistingItems();

        // Added before the old key is dropped so MySQL never loses the index
        // the meal_plan_id foreign key leans on.
        Schema::table('meal_plan_items', function (Blueprint $table) {
            $table->unique(
                ['meal_plan_id', 'recipe_id', 'planned_for', 'meal_slot'],
                'meal_plan_items_plan_recipe_day_slot_unique'
            );
        });

        Schema::table('meal_plan_items', function (Blueprint $table) {
            $table->dropUnique('meal_plan_items_meal_plan_id_recipe_id_unique');
            $table->index(['meal_plan_id', 'planned_for']);
        });
    }

    public function down(): void
    {
        Schema::table('meal_plan_items', function (Blueprint $table) {
            $table->unique(['meal_plan_id', 'recipe_id']);
        });

        Schema::table('meal_plan_items', function (Blueprint $table) {
            $table->dropIndex(['meal_plan_id', 'planned_for']);
            $table->dropUnique('meal_plan_items_plan_recipe_day_slot_unique');
            $table->dropColumn(['planned_for', 'meal_slot']);
        });
    }

    /**
     * Dishes picked before the calendar existed land on their plan's first day,
     * which is where the old undated list was effectively showing them.
     */
    private function backfillExistingItems(): void
    {
        DB::table('meal_plans')
            ->select(['id', 'starts_on'])
            ->orderBy('id')
            ->chunk(200, function ($plans) {
                foreach ($plans as $plan) {
                    DB::table('meal_plan_items')
                        ->where('meal_plan_id', $plan->id)
                        ->whereNull('planned_for')
                        ->update(['planned_for' => $plan->starts_on]);
                }
            });
    }
};
