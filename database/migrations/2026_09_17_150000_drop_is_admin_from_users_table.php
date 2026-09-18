<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The contract half of the role migration.
     *
     * Every reader moved onto users.role two commits ago, so the boolean is
     * now written by nothing but the hook keeping the pair in step. Both go
     * together: leaving the column would leave a second answer to the same
     * question, and a dropped column reads as null rather than raising, so a
     * straggler would quietly turn an admin into a member.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            /** MySQL needs the index gone before the column it indexes. */
            $table->dropIndex(['is_admin']);
            $table->dropColumn('is_admin');
        });
    }

    /**
     * Genuinely reversible: the role is the source, so the boolean can be
     * rebuilt from it exactly.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('password');
        });

        DB::table('users')->where('role', UserRole::Admin->value)->update(['is_admin' => true]);

        Schema::table('users', function (Blueprint $table) {
            $table->index('is_admin');
        });
    }
};
