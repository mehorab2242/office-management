<?php

namespace Tests\Feature;

use Tests\TestCase;

class FrontendShellTest extends TestCase
{
    public function test_spa_routes_render_the_frontend_shell(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('id="app"', false);

        $this->get('/expenses/create')
            ->assertOk()
            ->assertSee('id="app"', false);
    }
}
