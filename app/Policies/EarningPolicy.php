<?php

namespace App\Policies;

use App\Models\Earning;
use App\Models\User;

class EarningPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->isSuperAdmin();
    }

    public function view(User $user, Earning $earning): bool
    {
        return $user->is_active && $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->isSuperAdmin();
    }

    public function update(User $user, Earning $earning): bool
    {
        return $user->is_active && $user->isSuperAdmin();
    }

    public function delete(User $user, Earning $earning): bool
    {
        return $user->is_active && $user->isSuperAdmin();
    }
}
