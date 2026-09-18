<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpaEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_spa_root_returns_ok_view(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('id="root"', false);
        $response->assertSee('Panchforon', false);
    }

    public function test_spa_fallback_routes_load_root_view(): void
    {
        $this->get('/meal-plan')->assertStatus(200)->assertSee('id="root"', false);
        $this->get('/shopping-list')->assertStatus(200)->assertSee('id="root"', false);
        $this->get('/recipes/bhuna-khichuri')->assertStatus(200)->assertSee('id="root"', false);
        $this->get('/login')->assertStatus(200)->assertSee('id="root"', false);
    }

    public function test_api_routes_are_not_captured_by_spa_fallback(): void
    {
        $response = $this->getJson('/api/recipes');

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
    }

    /**
     * Both panels sign a guest in rather than handing them the SPA. Getting
     * the storefront's HTML at /admin would look like the panel is broken.
     */
    public function test_filament_panels_are_not_captured_by_spa_fallback(): void
    {
        foreach (['/admin', '/studio'] as $panel) {
            $this->get($panel)
                ->assertRedirect($panel.'/login');

            $this->get($panel.'/login')
                ->assertStatus(200)
                ->assertDontSee('id="root"', false);
        }
    }

    /**
     * A missing asset must 404, not come back as the SPA's HTML with a 200.
     * Filament's JS lives under /js/filament and is published by
     * `filament:upgrade` rather than committed; when it was absent the
     * fallback served this view in its place, so every Filament Alpine
     * component silently failed to define itself.
     */
    public function test_missing_assets_are_not_captured_by_spa_fallback(): void
    {
        $this->get('/js/filament/support/support.js')->assertNotFound();
        $this->get('/build/assets/does-not-exist.css')->assertNotFound();
        $this->get('/css/filament/filament/app.css')->assertNotFound();
        $this->get('/fonts/nothing-here.woff2')->assertNotFound();
        $this->get('/icons/nothing-here.png')->assertNotFound();
    }
}
