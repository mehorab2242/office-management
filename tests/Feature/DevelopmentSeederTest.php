<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DevelopmentSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_development_seed_provides_demo_roles_categories_and_expenses(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('users', ['email' => 'admin@example.test', 'role' => User::ROLE_SUPER_ADMIN]);
        $this->assertDatabaseHas('users', ['email' => 'staff@example.test', 'role' => User::ROLE_STAFF]);
        $this->assertDatabaseCount('categories', 4);
        $this->assertDatabaseCount('expenses', 3);
        $this->postJson('/api/login', [
            'email' => 'admin@example.test',
            'password' => 'OfficeDemo123!',
        ])->assertOk();
    }
}
