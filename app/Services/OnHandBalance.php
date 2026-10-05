<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\OnHandReceipt;
use App\Models\User;

class OnHandBalance
{
    /**
     * Money a staff member received from admin minus the expenses that staff member recorded.
     *
     * @return array{total_received: string, total_expenses: string, current_on_hand: string}
     */
    public function summary(User $user): array
    {
        $received = bcadd((string) OnHandReceipt::query()->where('user_id', $user->id)->sum('amount'), '0', 2);
        $expenses = bcadd((string) Expense::query()->where('created_by', $user->id)->sum('amount'), '0', 2);

        return ['total_received' => $received, 'total_expenses' => $expenses, 'current_on_hand' => bcsub($received, $expenses, 2)];
    }
}
