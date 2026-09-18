<?php

use App\Rules\YouTubeVideoLink;
use Illuminate\Support\Facades\Validator;

/**
 * The point of the rule: a form may accept the URL someone copied out of the
 * address bar, while the column only ever sees the id.
 */
test('a pasted link validates and comes back as the bare id', function (string $pasted) {
    $validator = Validator::make(
        ['youtube_video_id' => $pasted],
        ['youtube_video_id' => ['nullable', new YouTubeVideoLink]],
    );

    expect($validator->passes())->toBeTrue()
        ->and($validator->validated()['youtube_video_id'])->toBe('dQw4w9WgXcQ');
})->with([
    'https://www.youtube.com/watch?v=dQw4w9WgXcQ&list=PLbpi6ZahtOH6',
    'https://youtu.be/dQw4w9WgXcQ?t=42',
    'youtube.com/shorts/dQw4w9WgXcQ',
    'dQw4w9WgXcQ',
]);

test('a field left empty is left alone', function () {
    $validator = Validator::make(
        ['youtube_video_id' => null],
        ['youtube_video_id' => ['nullable', new YouTubeVideoLink]],
    );

    expect($validator->passes())->toBeTrue()
        ->and($validator->validated()['youtube_video_id'])->toBeNull();
});

test('anything that is not a youtube video is refused', function (mixed $value) {
    $validator = Validator::make(
        ['youtube_video_id' => $value],
        ['youtube_video_id' => [new YouTubeVideoLink]],
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('youtube_video_id'))->toContain('YouTube video link');
})->with([
    'another site' => 'https://vimeo.com/76979871',
    'prose' => 'the one with the chicken',
    'a channel' => 'https://www.youtube.com/@panchforon',
    'not a string at all' => 12345,
    'an array' => [['dQw4w9WgXcQ']],
]);
