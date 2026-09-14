<?php

use App\Enums\UnitDimension;
use App\Filament\Actions\UnitActions;
use App\Filament\Resources\Units\Pages\CreateUnit;
use App\Filament\Resources\Units\Pages\EditUnit;
use App\Filament\Resources\Units\Pages\ListUnits;
use App\Filament\Resources\Units\UnitResource;
use App\Models\Ingredient;
use App\Models\RecipeIngredient;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * The units table is reference data the merge engine reads on every shopping
 * list, and the migration writes its fourteen rows, so these tests work from
 * the real ones rather than from a factory.
 */
beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

test('a guest is sent to the login screen rather than the units table', function () {
    $this->get(UnitResource::getUrl('index'))->assertRedirect('/admin/login');
});

test('an ordinary cook is refused the units screen and is told nothing by it', function () {
    $member = User::factory()->create();

    foreach ([UnitResource::getUrl('index'), UnitResource::getUrl('edit', ['record' => 'tbsp'])] as $url) {
        $body = $this->actingAs($member)->get($url)->assertForbidden()->getContent();

        expect($body)
            ->not->toContain('tablespoon')
            ->not->toContain('14.7868')
            ->not->toContain('factor_to_canonical');
    }
});

test('a suspended admin is refused the units screen', function () {
    $this->actingAs(User::factory()->admin()->suspended()->create())
        ->get(UnitResource::getUrl('index'))
        ->assertForbidden();
});

test('an admin reaches every units page', function () {
    foreach ([
        UnitResource::getUrl('index'),
        UnitResource::getUrl('create'),
        UnitResource::getUrl('edit', ['record' => 'tbsp']),
    ] as $url) {
        $this->actingAs($this->admin)->get($url)->assertSuccessful();
    }
});

test('an admin can add the unit an importer keeps meeting', function () {
    Livewire::actingAs($this->admin)
        ->test(CreateUnit::class)
        ->fillForm([
            'symbol' => ' Pinch ',
            'name' => 'pinch',
            'dimension' => UnitDimension::Volume->value,
            'factor_to_canonical' => 0.31,
            'position' => 150,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Unit::find('pinch'))->not->toBeNull()
        ->and(Unit::find('pinch')->dimension)->toBe(UnitDimension::Volume);
});

test('an admin can correct a unit', function () {
    Livewire::actingAs($this->admin)
        ->test(EditUnit::class, ['record' => 'cup'])
        ->fillForm([
            'name' => 'US cup',
            'factor_to_canonical' => 240,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $cup = Unit::find('cup');

    expect($cup->name)->toBe('US cup')
        ->and($cup->factor_to_canonical)->toBe(240.0);
});

test('the symbol is fixed once a unit exists, because recipe lines store it', function () {
    RecipeIngredient::factory()->create(['unit' => 'tbsp']);

    Livewire::actingAs($this->admin)
        ->test(EditUnit::class, ['record' => 'tbsp'])
        ->fillForm(['symbol' => 'tablespoon'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Unit::find('tbsp'))->not->toBeNull()
        ->and(Unit::find('tablespoon'))->toBeNull()
        ->and(RecipeIngredient::where('unit', 'tbsp')->count())->toBe(1);
});

test('a unit recipe lines are written in cannot be deleted', function () {
    RecipeIngredient::factory()->count(2)->create(['unit' => 'tbsp']);

    Livewire::actingAs($this->admin)
        ->test(ListUnits::class)
        ->callTableAction('delete', Unit::find('tbsp'));

    expect(Unit::find('tbsp'))->not->toBeNull();
});

test('a unit an ingredient is usually written in cannot be deleted', function () {
    Ingredient::factory()->create(['preferred_unit' => 'clove']);

    Livewire::actingAs($this->admin)
        ->test(ListUnits::class)
        ->callTableAction('delete', Unit::find('clove'));

    expect(Unit::find('clove'))->not->toBeNull();
});

test('a unit nothing depends on can be deleted', function () {
    Livewire::actingAs($this->admin)
        ->test(ListUnits::class)
        ->callTableAction('delete', Unit::find('floz'));

    expect(Unit::find('floz'))->toBeNull();
});

test('the usage summary names what is standing in the way', function () {
    RecipeIngredient::factory()->create(['unit' => 'g']);
    Ingredient::factory()->create(['preferred_unit' => 'g']);

    expect(UnitActions::usageSummary(Unit::find('g')))
        ->toContain('1 recipe line')
        ->toContain('1 ingredient')
        ->and(UnitActions::usageSummary(Unit::find('floz')))->toBeNull();
});

test('the list counts the recipe lines riding on each unit', function () {
    RecipeIngredient::factory()->count(3)->create(['unit' => 'g']);
    RecipeIngredient::factory()->create(['unit' => 'ml']);

    $gram = UnitResource::getEloquentQuery()->whereKey('g')->first();

    expect((int) $gram->recipe_lines_count)->toBe(3);
});

test('the mass dimension reads as Weight without the stored value changing', function () {
    Livewire::actingAs($this->admin)
        ->test(ListUnits::class)
        ->assertSee('Weight')
        ->assertDontSee('Mass');

    expect(Unit::find('g')->dimension->value)->toBe('mass');
});
