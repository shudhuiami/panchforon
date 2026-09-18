<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('creator_applications', function (Blueprint $table) {
            $table->id();
            /**
             * Cascading rather than nulling, unlike a report: a report about a
             * recipe still stands once the reporter closes their account, but
             * an application to become a creator is only ever about the person
             * who wrote it, so without them there is nothing left to decide.
             */
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->text('pitch');
            $table->string('youtube_channel_url')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();

            /**
             * Deliberately not unique on user_id: someone declined once may
             * come back with a stronger pitch, and the earlier attempt stays
             * on file rather than being overwritten.
             */
            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('creator_applications');
    }
};
