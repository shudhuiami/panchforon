<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            /**
             * Defaults to approved so that existing rows and TheMealDB imports
             * stay visible. New user submissions are set to pending explicitly
             * when they are created.
             */
            $table->string('moderation_status', 20)
                ->default('approved')
                ->after('source');

            $table->timestamp('moderated_at')->nullable()->after('moderation_status');
            $table->foreignId('moderated_by')
                ->nullable()
                ->after('moderated_at')
                ->constrained('users')
                ->nullOnDelete();

            $table->index(['moderation_status', 'source']);
        });
    }

    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropForeign(['moderated_by']);
            $table->dropIndex(['moderation_status', 'source']);
            $table->dropColumn(['moderation_status', 'moderated_at', 'moderated_by']);
        });
    }
};
