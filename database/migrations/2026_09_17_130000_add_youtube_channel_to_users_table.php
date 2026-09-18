<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A creator links one YouTube channel to their account.
     *
     * The channel id is the identity — a handle can be changed by its owner
     * and a title changes whenever they feel like it, so both are kept only to
     * show the link without spending a quota unit on every page render. It is
     * indexed because the channel id is how a channel is looked up, and how a
     * second account claiming the same channel is caught.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('youtube_channel_id', 64)->nullable()->after('suspension_reason');
            $table->string('youtube_channel_handle', 100)->nullable()->after('youtube_channel_id');
            $table->string('youtube_channel_title')->nullable()->after('youtube_channel_handle');

            $table->index('youtube_channel_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['youtube_channel_id']);
            $table->dropColumn(['youtube_channel_id', 'youtube_channel_handle', 'youtube_channel_title']);
        });
    }
};
