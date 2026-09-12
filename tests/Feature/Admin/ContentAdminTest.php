<?php

use App\Filament\Resources\ContentBlocks\ContentBlockResource;
use App\Filament\Resources\ContentBlocks\Pages\EditContentBlock;
use App\Filament\Resources\ContentBlocks\Pages\ListContentBlocks;
use App\Filament\Resources\Pages\PageResource;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Models\ContentBlock;
use App\Models\Page;
use App\Models\User;
use App\Services\ContentRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
});

test('an ordinary cook cannot reach the content screens', function () {
    $cook = User::factory()->create();

    $this->actingAs($cook)->get(PageResource::getUrl('index'))->assertForbidden();
    $this->actingAs($cook)->get(ContentBlockResource::getUrl('index'))->assertForbidden();
});

test('an admin can write a page and it goes live', function () {
    Livewire::actingAs($this->admin)
        ->test(CreatePage::class)
        ->fillForm([
            'title' => 'About Panchforon',
            'slug' => 'about',
            'body' => 'We cook **together**.',
            'is_published' => true,
            'show_in_footer' => true,
            'position' => 1,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->getJson('/api/pages/about')->assertOk()->assertJsonPath('data.title', 'About Panchforon');
    $this->getJson('/api/pages')->assertJsonPath('data.0.slug', 'about');
});

test('the address is suggested from the title while the page is new', function () {
    Livewire::actingAs($this->admin)
        ->test(CreatePage::class)
        ->fillForm(['title' => 'Our Privacy Promise'])
        ->assertFormSet(['slug' => 'our-privacy-promise']);
});

test('two pages cannot share an address', function () {
    Page::factory()->create(['slug' => 'about']);

    Livewire::actingAs($this->admin)
        ->test(CreatePage::class)
        ->fillForm(['title' => 'About', 'slug' => 'about', 'body' => 'Anything.'])
        ->call('create')
        ->assertHasFormErrors(['slug']);
});

test('unpublishing a page takes it off the site straight away', function () {
    $page = Page::factory()->inFooter()->create(['slug' => 'terms']);
    $this->getJson('/api/pages/terms')->assertOk();

    Livewire::actingAs($this->admin)
        ->test(EditPage::class, ['record' => $page->getKey()])
        ->fillForm(['is_published' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->getJson('/api/pages/terms')->assertNotFound();
    $this->getJson('/api/pages')->assertJsonCount(0, 'data');
});

test('the wording screen lists every editable string, not only the saved ones', function () {
    expect(ContentBlock::count())->toBe(0);

    Livewire::actingAs($this->admin)->test(ListContentBlocks::class);

    expect(ContentBlock::pluck('key')->all())->toEqualCanonicalizing(array_keys(ContentRepository::BLOCKS));
});

test('editing a piece of wording changes the storefront at once', function () {
    app(ContentRepository::class)->seedMissingBlocks();
    $block = ContentBlock::where('key', 'home_cta_title')->sole();
    $this->getJson('/api/content-blocks')->assertOk();

    Livewire::actingAs($this->admin)
        ->test(EditContentBlock::class, ['record' => $block->getKey()])
        ->fillForm(['body' => 'Share the dish you are proud of.'])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->getJson('/api/content-blocks')->assertJsonPath('data.home_cta_title', 'Share the dish you are proud of.');
});

test('wording cannot be created or deleted from the panel', function () {
    expect(ContentBlockResource::canCreate())->toBeFalse()
        ->and($this->admin->can('delete', ContentBlock::factory()->create()))->toBeFalse();
});
