<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('password');

            /**
             * Suspension is a reversible timestamp rather than a soft delete:
             * soft-deleting a user would hide them from the recipes and ratings
             * relations that the public API already joins against.
             */
            $table->timestamp('suspended_at')->nullable()->after('is_admin');
            $table->string('suspension_reason')->nullable()->after('suspended_at');

            $table->index('is_admin');
            $table->index('suspended_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_admin']);
            $table->dropIndex(['suspended_at']);
            $table->dropColumn(['is_admin', 'suspended_at', 'suspension_reason']);
        });
    }
};
