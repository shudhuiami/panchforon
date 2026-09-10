<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shopping_list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meal_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_id')->nullable()->constrained()->nullOnDelete();
            $table->string('display_name');
            $table->decimal('quantity', 10, 3)->nullable();
            $table->string('unit')->nullable();
            $table->boolean('is_unmerged')->default(false);
            $table->string('source_note')->nullable();
            $table->boolean('is_checked')->default(false);
            $table->timestamps();

            $table->index(['meal_plan_id', 'is_checked']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopping_list_items');
    }
};
