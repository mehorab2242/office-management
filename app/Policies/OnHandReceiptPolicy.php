<?php

namespace App\Policies;

use App\Models\OnHandReceipt;
use App\Models\User;

class OnHandReceiptPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->role === User::ROLE_STAFF;
    }

    public function view(User $user, OnHandReceipt $onHandReceipt): bool
    {
        return $this->viewAny($user) && $onHandReceipt->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, OnHandReceipt $onHandReceipt): bool
    {
        return $this->view($user, $onHandReceipt);
    }

    public function delete(User $user, OnHandReceipt $onHandReceipt): bool
    {
        return $this->view($user, $onHandReceipt);
    }
}
