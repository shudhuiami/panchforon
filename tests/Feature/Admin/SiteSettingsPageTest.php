<?php

use App\Filament\Pages\SiteSettings;
use App\Models\Setting;
use App\Models\User;
use App\Services\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('the settings screen loads the current values', function () {
    app(SettingsRepository::class)->setMany([
        'site_name' => 'Panchforon Kitchen',
        'registration_open' => false,
    ]);

    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(SiteSettings::class)
        ->assertFormSet([
            'site_name' => 'Panchforon Kitchen',
            'registration_open' => false,
            'submissions_open' => true,
        ]);
});

test('saving persists the values and takes effect immediately', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(SiteSettings::class)
        ->fillForm([
            'site_name' => 'Panchforon Test Kitchen',
            'contact_email' => 'hello@panchforon.test',
            'registration_open' => false,
            'submissions_open' => false,
            'mealdb_import_limit' => 50,
            'mealdb_import_areas' => ['Thai', 'Japanese'],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $settings = app(SettingsRepository::class);

    expect($settings->string('site_name'))->toBe('Panchforon Test Kitchen')
        ->and($settings->boolean('registration_open'))->toBeFalse()
        ->and($settings->integer('mealdb_import_limit'))->toBe(50)
        ->and($settings->array('mealdb_import_areas'))->toBe(['Thai', 'Japanese'])
        ->and(Setting::query()->count())->toBe(6);

    /** The toggle is live in the same request cycle, without a cache clear. */
    $this->postJson('/api/register', [
        'name' => 'Blocked',
        'email' => 'blocked@example.com',
        'password' => 'password123',
    ])->assertStatus(403);
});

test('the settings form validates', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(SiteSettings::class)
        ->fillForm(['site_name' => '', 'contact_email' => 'not-an-email'])
        ->call('save')
        ->assertHasFormErrors(['site_name', 'contact_email']);

    expect(Setting::query()->count())->toBe(0);
});

test('the settings screen never renders a mail credential', function () {
    config()->set('mail.mailers.smtp.username', 'smtp-user@panchforon.test');
    config()->set('mail.mailers.smtp.password', 'super-secret-smtp-password');

    $this->actingAs(User::factory()->admin()->create());

    $html = Livewire::test(SiteSettings::class)->html();

    expect($html)
        ->not->toContain('super-secret-smtp-password')
        ->not->toContain('smtp-user@panchforon.test')
        ->toContain('configured');
});

test('the settings screen reports when mail is not configured', function () {
    config()->set('mail.mailers.smtp.username', null);
    config()->set('mail.mailers.smtp.password', null);

    $this->actingAs(User::factory()->admin()->create());

    expect(Livewire::test(SiteSettings::class)->html())->toContain('not configured');
});

test('a non-admin is refused the settings screen', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(SiteSettings::class)->assertForbidden();
});

test('a non-admin saving settings changes nothing', function () {
    $this->actingAs(User::factory()->create());

    rescue(fn () => Livewire::test(SiteSettings::class)
        ->fillForm(['site_name' => 'Hijacked'])
        ->call('save'), report: false);

    expect(Setting::query()->count())->toBe(0)
        ->and(app(SettingsRepository::class)->string('site_name'))->toBe('Panchforon');
});

test('a suspended admin is refused the settings screen', function () {
    $this->actingAs(User::factory()->admin()->suspended()->create());

    Livewire::test(SiteSettings::class)->assertForbidden();
});
