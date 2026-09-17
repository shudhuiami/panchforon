<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            /**
             * A plain string rather than a database enum: adding a fourth role
             * later should be a change to App\Enums\UserRole alone, not an
             * ALTER on a table every request reads. Twenty characters is room
             * enough for the cases we can imagine, and the default keeps every
             * existing row valid without touching it.
             */
            $table->string('role', 20)->default(UserRole::Member->value)->after('password');

            $table->index('role');
        });

        /**
         * Backfill: is_admin is still the column the application reads, so the
         * two must agree from the moment this migration finishes. Done with
         * the query builder rather than Eloquent so it stays correct even when
         * the model has moved on past this point in the refactor.
         */
        DB::table('users')
            ->where('is_admin', true)
            ->update(['role' => UserRole::Admin->value]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn('role');
        });
    }
};
