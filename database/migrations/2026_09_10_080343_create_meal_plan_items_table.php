<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meal_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meal_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('servings')->default(4);
            $table->timestamps();

            $table->unique(['meal_plan_id', 'recipe_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meal_plan_items');
    }
};
