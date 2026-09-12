<?php

use App\Models\Page;
use App\Models\Recipe;
use App\Models\User;
use App\Services\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a shared recipe link previews as that recipe', function () {
    $recipe = Recipe::factory()->create([
        'slug' => 'chittagong-beef-kala-bhuna',
        'title' => 'Chittagong Beef Kala Bhuna',
        'cuisine' => 'Bangladeshi',
        'image_url' => 'https://img.test/bhuna.jpg',
    ]);

    $this->get("/recipes/{$recipe->slug}")
        ->assertOk()
        ->assertSee('<meta property="og:title" content="Chittagong Beef Kala Bhuna — Panchforon">', false)
        ->assertSee('<meta property="og:image" content="https://img.test/bhuna.jpg">', false)
        ->assertSee('<meta name="twitter:card" content="summary_large_image">', false);
});

test('a recipe the catalogue hides never previews', function () {
    $recipe = Recipe::factory()->awaitingModeration()->create(['slug' => 'held-back', 'title' => 'Held Back Curry']);

    $this->get("/recipes/{$recipe->slug}")->assertOk()->assertDontSee('Held Back Curry', false);
});

test('a shared page link previews as that page', function () {
    Page::factory()->create(['slug' => 'about', 'title' => 'About us', 'meta_description' => 'Who cooks here.']);

    $this->get('/p/about')
        ->assertOk()
        ->assertSee('<meta property="og:title" content="About us — Panchforon">', false)
        ->assertSee('<meta property="og:description" content="Who cooks here.">', false);
});

test('a shared cook link previews as that cook', function () {
    $cook = User::factory()->create(['name' => 'Ahmed Zobayer']);

    $this->get("/cooks/{$cook->id}")
        ->assertOk()
        ->assertSee('<meta property="og:title" content="Ahmed Zobayer — Panchforon">', false)
        ->assertSee('<meta property="og:type" content="profile">', false);
});

test('a suspended cook has no preview of their own', function () {
    $cook = User::factory()->create(['name' => 'Spammer McSpam', 'suspended_at' => now()]);

    $this->get("/cooks/{$cook->id}")->assertOk()->assertDontSee('Spammer McSpam', false);
});

test('every other path falls back to the site description', function () {
    $this->get('/recipes')
        ->assertOk()
        ->assertSee('<meta property="og:type" content="website">', false)
        ->assertSee('recipes worth cooking twice', false);
});

test('the preview title follows the site name an admin set', function () {
    app(SettingsRepository::class)->set('site_name', 'Panchforon Kitchen');

    $this->get('/')->assertOk()->assertSee('Panchforon Kitchen — recipes worth cooking twice', false);
});
