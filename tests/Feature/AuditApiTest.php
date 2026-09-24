<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_read_paginated_audit_log_without_password_values(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->postJson('/api/users', [
            'name' => 'Office Staff', 'email' => 'new@example.test',
            'password' => 'long-password-123', 'role' => 'staff',
        ])->assertCreated();

        $response = $this->getJson('/api/audit-logs')->assertOk()
            ->assertJsonPath('data.0.action', 'user.created')
            ->assertJsonPath('meta.total', 1);
        $this->assertStringNotContainsString('long-password-123', $response->getContent());
    }

    public function test_staff_cannot_read_audit_log(): void
    {
        $this->actingAs(User::factory()->staff()->create())
            ->getJson('/api/audit-logs')->assertForbidden();
    }
}
