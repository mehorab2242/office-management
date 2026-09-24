<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_deactivate_a_staff_user(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $id = $this->postJson('/api/users', [
            'name' => 'Office Staff',
            'email' => 'staff@example.test',
            'password' => 'long-password-123',
            'role' => 'staff',
        ])->assertCreated()->assertJsonPath('data.role', 'staff')->json('data.id');

        $this->putJson("/api/users/{$id}", ['is_active' => false])
            ->assertOk()->assertJsonPath('data.is_active', false);
        $this->assertDatabaseHas('users', ['id' => $id, 'is_active' => false]);
    }

    public function test_staff_cannot_manage_users(): void
    {
        $this->actingAs(User::factory()->staff()->create())
            ->getJson('/api/users')->assertForbidden();
    }
}
