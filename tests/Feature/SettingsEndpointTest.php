<?php

use App\Services\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the public settings endpoint serves the configured defaults', function () {
    $this->getJson('/api/settings')
        ->assertStatus(200)
        ->assertJsonPath('data.site_name', 'Panchforon')
        ->assertJsonPath('data.registration_open', true)
        ->assertJsonPath('data.submissions_open', true);
});

test('the public settings endpoint reflects what an admin saved', function () {
    app(SettingsRepository::class)->setMany([
        'site_name' => 'Panchforon Kitchen',
        'contact_email' => 'hello@panchforon.test',
        'submissions_open' => false,
    ]);

    $this->getJson('/api/settings')
        ->assertJsonPath('data.site_name', 'Panchforon Kitchen')
        ->assertJsonPath('data.contact_email', 'hello@panchforon.test')
        ->assertJsonPath('data.submissions_open', false);
});

test('the public settings endpoint never exposes other settings', function () {
    app(SettingsRepository::class)->set('mealdb_import_limit', 5);

    $json = $this->getJson('/api/settings')->json('data');

    expect(array_keys($json))->toBe(['site_name', 'contact_email', 'registration_open', 'submissions_open']);
});
