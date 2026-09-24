<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('office:create-admin')]
#[Description('Create an office administrator with a securely prompted password')]
class CreateOfficeAdmin extends Command
{
    public function handle(): int
    {
        $email = trim((string) $this->ask('Email address'));
        $name = trim((string) $this->ask('Name'));
        $password = (string) $this->secret('Password');
        $confirmation = (string) $this->secret('Confirm password');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || $name === '' || mb_strlen($password) < 12 || $password !== $confirmation) {
            $this->error('Provide a valid email, name, and matching password of at least 12 characters.');

            return self::FAILURE;
        }
        if (User::where('email', $email)->exists()) {
            $this->error('A user with that email already exists.');

            return self::FAILURE;
        }

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);
        $this->info('Administrator created.');

        return self::SUCCESS;
    }
}
