<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('source', ['api', 'user'])->default('user');
            $table->string('external_id')->nullable()->unique();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('cuisine')->nullable();
            $table->string('category')->nullable();
            $table->text('instructions');
            $table->string('image_url', 2048)->nullable();
            $table->unsignedSmallInteger('servings')->default(4);
            $table->string('source_url', 2048)->nullable();
            $table->timestamps();

            $table->index(['cuisine', 'category', 'source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipes');
    }
};
