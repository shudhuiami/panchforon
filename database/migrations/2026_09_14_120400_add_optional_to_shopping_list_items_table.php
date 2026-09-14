<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A merged line is optional only when every recipe that contributed to it
     * said so — one dish needing the yogurt is enough to make it a real item
     * on the list.
     */
    public function up(): void
    {
        Schema::table('shopping_list_items', function (Blueprint $table) {
            $table->boolean('is_optional')->default(false)->after('is_unmerged');
        });
    }

    public function down(): void
    {
        Schema::table('shopping_list_items', function (Blueprint $table) {
            $table->dropColumn('is_optional');
        });
    }
};
