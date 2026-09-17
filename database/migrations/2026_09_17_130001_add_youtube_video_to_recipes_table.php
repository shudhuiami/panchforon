<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The video a recipe is cooked in, alongside the other media it carries.
     *
     * Eleven characters is the whole of a YouTube id, so the column is sized to
     * hold one and nothing like a URL: what goes in here is rendered straight
     * into an iframe src, and the only safe thing to put there is an id.
     */
    public function up(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->string('youtube_video_id', 20)->nullable()->after('source_url');
        });
    }

    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropColumn('youtube_video_id');
        });
    }
};
