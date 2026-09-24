<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiErrorTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_errors_have_consistent_json_envelope(): void
    {
        $this->getJson('/api/expenses')->assertUnauthorized()->assertJsonPath('success', false);

        $this->actingAs(User::factory()->staff()->create())
            ->postJson('/api/categories', ['name' => 'Denied'])
            ->assertForbidden()->assertJsonPath('success', false);

        $this->getJson('/api/expenses/999999')
            ->assertNotFound()->assertJsonPath('success', false);

        $this->postJson('/api/expenses', [])
            ->assertUnprocessable()->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['description', 'amount']);
    }
}
