<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipe_stats', function (Blueprint $table) {
            $table->foreignId('recipe_id')->primary()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('ratings_count')->default(0);
            $table->decimal('ratings_avg', 3, 2)->nullable();
            $table->decimal('bayesian_score', 5, 4)->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->index('bayesian_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_stats');
    }
};
