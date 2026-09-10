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
}
