<?php

use App\Models\ContentBlock;
use App\Models\Page;
use App\Services\ContentRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a published page is served with its body already rendered', function () {
    Page::factory()->create([
        'slug' => 'about',
        'title' => 'About Panchforon',
        'body' => "# Our kitchen\n\nWe cook **together**.",
    ]);

    $this->getJson('/api/pages/about')
        ->assertOk()
        ->assertJsonPath('data.title', 'About Panchforon')
        ->assertJsonPath('data.html', "<h1>Our kitchen</h1>\n<p>We cook <strong>together</strong>.</p>\n");
});

test('markup pasted into a page body is escaped, not executed', function () {
    Page::factory()->create(['slug' => 'risky', 'body' => '<script>alert(1)</script>']);

    $html = $this->getJson('/api/pages/risky')->assertOk()->json('data.html');

    expect($html)->not->toContain('<script>')
        ->and($html)->toContain('&lt;script&gt;');
});

test('a draft page is not readable', function () {
    Page::factory()->draft()->create(['slug' => 'secret-plans']);

    $this->getJson('/api/pages/secret-plans')->assertNotFound();
});

test('the footer lists only published pages marked for it, in order', function () {
    Page::factory()->inFooter()->create(['slug' => 'terms', 'title' => 'Terms', 'position' => 2]);
    Page::factory()->inFooter()->create(['slug' => 'privacy', 'title' => 'Privacy', 'position' => 1]);
    Page::factory()->inFooter()->draft()->create(['slug' => 'draft-page']);
    Page::factory()->create(['slug' => 'not-in-footer']);

    $json = $this->getJson('/api/pages')->assertOk()->json('data');

    expect(collect($json)->pluck('slug')->all())->toBe(['privacy', 'terms']);
});

test('content blocks fall back to the built-in wording', function () {
    $blocks = $this->getJson('/api/content-blocks')->assertOk()->json('data');

    expect($blocks['home_cta_title'])->toBe(ContentRepository::BLOCKS['home_cta_title']['body'])
        ->and(array_keys($blocks))->toBe(array_keys(ContentRepository::BLOCKS));
});

test('an edited content block replaces the default at once', function () {
    $this->getJson('/api/content-blocks')->assertOk();

    ContentBlock::create(['key' => 'home_cta_title', 'label' => 'Home call-to-action heading', 'body' => 'Share the dish you are proud of.']);

    $this->getJson('/api/content-blocks')->assertJsonPath('data.home_cta_title', 'Share the dish you are proud of.');
});

test('a block left blank keeps the built-in wording', function () {
    ContentBlock::create(['key' => 'footer_blurb', 'label' => 'Footer blurb', 'body' => '   ']);

    $this->getJson('/api/content-blocks')
        ->assertJsonPath('data.footer_blurb', ContentRepository::BLOCKS['footer_blurb']['body']);
});
