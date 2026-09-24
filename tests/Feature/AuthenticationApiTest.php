<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_issues_a_token_and_logout_revokes_it(): void
    {
        $user = User::factory()->create(['password' => 'correct-password']);

        $token = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'correct-password',
        ])->assertOk()->assertJsonPath('data.user.id', $user->id)->json('data.token');

        $this->withToken($token)->getJson('/api/me')->assertOk()->assertJsonPath('data.email', $user->email);
        $this->withToken($token)->postJson('/api/logout')->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 0);
        app('auth')->forgetGuards();
        $this->withToken($token)->getJson('/api/me')->assertUnauthorized();
    }

    public function test_invalid_credentials_and_inactive_users_cannot_login(): void
    {
        $user = User::factory()->create(['password' => 'correct-password']);

        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'wrong'])
            ->assertUnprocessable();

        $user->update(['is_active' => false]);
        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'correct-password'])
            ->assertUnprocessable();
    }

    public function test_private_api_requires_authentication(): void
    {
        $this->getJson('/api/expenses')->assertUnauthorized();
        $this->getJson('/api/categories')->assertUnauthorized();
    }

    public function test_deactivated_user_cannot_use_an_existing_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('office-api')->plainTextToken;
        $user->update(['is_active' => false]);

        $this->withToken($token)->getJson('/api/me')->assertForbidden();
        $this->withToken($token)->getJson('/api/expenses')->assertForbidden();
    }
}
