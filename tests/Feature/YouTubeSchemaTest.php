<?php

use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('a cook can carry a linked channel', function () {
    expect(Schema::hasColumns('users', ['youtube_channel_id', 'youtube_channel_handle', 'youtube_channel_title']))->toBeTrue()
        ->and((new User)->getFillable())->toContain('youtube_channel_id', 'youtube_channel_handle', 'youtube_channel_title');
});

test('a recipe can carry a video', function () {
    expect(Schema::hasColumn('recipes', 'youtube_video_id'))->toBeTrue()
        ->and((new Recipe)->getFillable())->toContain('youtube_video_id');
});
