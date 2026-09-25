<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_and_deactivate_a_staff_user(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create());

        $id = $this->postJson('/api/users', [
            'name' => 'Office Staff',
            'email' => 'staff@example.test',
            'password' => 'long-password-123',
            'password_confirmation' => 'long-password-123',
        ])->assertCreated()
            ->assertJsonPath('data.role', 'staff')
            ->assertJsonPath('data.is_active', true)
            ->assertJsonStructure(['data' => ['id', 'name', 'email', 'role', 'is_active', 'created_at']])
            ->json('data.id');

        $this->putJson("/api/users/{$id}", ['is_active' => false])
            ->assertOk()->assertJsonPath('data.is_active', false);
        $this->assertDatabaseHas('users', ['id' => $id, 'is_active' => false]);
    }

    public function test_staff_cannot_manage_users(): void
    {
        $this->actingAs(User::factory()->staff()->create())
            ->getJson('/api/users')->assertForbidden();
    }

    public function test_super_admin_can_create_an_admin_user(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create());

        $this->postJson('/api/users', [
            'name' => 'New Admin',
            'email' => 'new.admin@example.test',
            'password' => 'long-password-123',
            'password_confirmation' => 'long-password-123',
            'role' => User::ROLE_SUPER_ADMIN,
        ])->assertCreated()->assertJsonPath('data.role', User::ROLE_SUPER_ADMIN);

        $this->assertDatabaseHas('users', ['email' => 'new.admin@example.test', 'role' => User::ROLE_SUPER_ADMIN]);
    }

    public function test_a_user_cannot_deactivate_or_delete_their_own_account(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->putJson("/api/users/{$user->id}", ['is_active' => false])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('is_active');

        $this->deleteJson("/api/users/{$user->id}")->assertMethodNotAllowed();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'is_active' => true]);
    }

    public function test_user_creation_rejects_unsupported_role_and_duplicate_email(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create());
        User::factory()->create(['email' => 'taken@example.test']);

        $this->postJson('/api/users', [
            'name' => 'Escalation Attempt',
            'email' => 'escalate@example.test',
            'password' => 'long-password-123',
            'password_confirmation' => 'long-password-123',
            'role' => 'owner',
        ])->assertUnprocessable()->assertJsonValidationErrors('role');
        $this->assertDatabaseMissing('users', ['email' => 'escalate@example.test']);

        $this->postJson('/api/users', [
            'name' => 'Duplicate Email',
            'email' => 'taken@example.test',
            'password' => 'long-password-123',
            'password_confirmation' => 'long-password-123',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_staff_creation_requires_a_matching_password_confirmation(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create());

        $this->postJson('/api/users', [
            'name' => 'Mismatched Password',
            'email' => 'mismatch@example.test',
            'password' => 'long-password-123',
            'password_confirmation' => 'different-password',
        ])->assertUnprocessable()->assertJsonValidationErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'mismatch@example.test']);
    }

    public function test_roles_cannot_be_changed_through_the_api(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $staff = User::factory()->staff()->create();

        $this->actingAs($superAdmin)
            ->putJson("/api/users/{$staff->id}", ['role' => 'super_admin'])
            ->assertOk();

        $this->assertDatabaseHas('users', ['id' => $staff->id, 'role' => 'staff']);
    }

    public function test_inactive_staff_cannot_authenticate(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $staff = User::factory()->staff()->create(['password' => 'staff-password-123']);

        $this->actingAs($superAdmin)
            ->putJson("/api/users/{$staff->id}", ['is_active' => false])
            ->assertOk()->assertJsonPath('data.is_active', false);

        $this->postJson('/api/login', [
            'email' => $staff->email,
            'password' => 'staff-password-123',
        ])->assertUnprocessable();
    }
}
