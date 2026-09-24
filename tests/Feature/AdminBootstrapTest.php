<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminBootstrapTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_admin_can_be_created_without_password_in_command_arguments(): void
    {
        $this->artisan('office:create-admin')
            ->expectsQuestion('Email address', 'owner@example.test')
            ->expectsQuestion('Name', 'Office Owner')
            ->expectsQuestion('Password', 'StrongTest123!')
            ->expectsQuestion('Confirm password', 'StrongTest123!')
            ->assertSuccessful();

        $this->assertDatabaseHas('users', ['email' => 'owner@example.test', 'role' => 'super_admin', 'is_active' => true]);
        $this->assertTrue(Hash::check('StrongTest123!', $this->app['db']->table('users')->where('email', 'owner@example.test')->value('password')));
    }
}
