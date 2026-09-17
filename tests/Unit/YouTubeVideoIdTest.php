<?php

use App\Support\YouTubeVideoId;

/**
 * Every shape below is the same video. What comes back is always the bare id,
 * because that is what gets written into an iframe src.
 */
test('the id comes out of every link people paste', function (string $input) {
    expect(YouTubeVideoId::fromInput($input))->toBe('dQw4w9WgXcQ');
})->with([
    'short link' => 'https://youtu.be/dQw4w9WgXcQ',
    'short link with a timestamp' => 'http://youtu.be/dQw4w9WgXcQ?t=42',
    'short link, no scheme, trailing slash' => 'youtu.be/dQw4w9WgXcQ/',
    'watch page' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
    'watch page inside a playlist' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ&list=PLbpi6ZahtOH6&index=2',
    'watch page, no scheme or www' => 'youtube.com/watch?v=dQw4w9WgXcQ',
    'watch page on mobile, v last' => 'https://m.youtube.com/watch?app=desktop&v=dQw4w9WgXcQ',
    'short' => 'https://www.youtube.com/shorts/dQw4w9WgXcQ',
    'short, no scheme, trailing slash' => 'youtube.com/shorts/dQw4w9WgXcQ/',
    'embed' => 'https://www.youtube.com/embed/dQw4w9WgXcQ?rel=0',
    'embed, no scheme' => 'youtube.com/embed/dQw4w9WgXcQ',
    'protocol relative' => '//youtu.be/dQw4w9WgXcQ',
    'surrounded by whitespace' => "  https://youtu.be/dQw4w9WgXcQ\n",
    'a bare id, so a stored value survives a round trip' => 'dQw4w9WgXcQ',
]);

test('anything that is not a youtube video is null', function (string $input) {
    expect(YouTubeVideoId::fromInput($input))->toBeNull();
})->with([
    'empty' => '',
    'whitespace' => '   ',
    'prose' => 'have a look at my video',
    'another site' => 'https://vimeo.com/76979871',
    /** The host must be YouTube itself, not something that merely ends in it. */
    'a lookalike host' => 'https://notyoutube.com/watch?v=dQw4w9WgXcQ',
    'youtube as a subdomain of somewhere else' => 'https://youtube.com.example.test/watch?v=dQw4w9WgXcQ',
    'youtube in the userinfo' => 'https://youtube.com@example.test/watch?v=dQw4w9WgXcQ',
    'youtube only in the fragment' => 'https://example.test/#youtu.be/dQw4w9WgXcQ',
    'a channel rather than a video' => 'https://www.youtube.com/@panchforon',
    'a watch page with no v' => 'https://www.youtube.com/watch?list=PLbpi6ZahtOH6',
    'an id that is too short' => 'https://www.youtube.com/watch?v=dQw4w9WgX',
    'an id that is too long' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQextra',
    'an id with characters ids do not have' => 'https://www.youtube.com/watch?v=dQw4w9WgX!Q',
    'a short link to nothing' => 'https://youtu.be/',
    'a script scheme' => 'javascript:alert(1)//youtu.be/dQw4w9WgXcQ',
    'a data url' => 'data:text/html,<iframe src="https://youtu.be/dQw4w9WgXcQ">',
]);

/**
 * The guarantee the embed leans on: whatever went in, what comes out is
 * eleven characters of the id alphabet and nothing that could redirect it.
 */
test('what comes back can never be a url', function (string $input) {
    $id = YouTubeVideoId::fromInput($input);

    expect($id)->toMatch('/^[A-Za-z0-9_-]{11}$/');
})->with([
    'https://www.youtube.com/watch?v=dQw4w9WgXcQ&feature=share',
    'https://youtu.be/_-aB9cD1efG',
    'youtube.com/shorts/ABCDEFGHIJK',
]);
