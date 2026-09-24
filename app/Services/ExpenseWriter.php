<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ExpenseWriter
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function create(array $validated, User $actor): Expense
    {
        return DB::transaction(function () use ($validated, $actor): Expense {
            $allocations = $validated['payer_allocations'] ?? [];
            unset($validated['payer_allocations']);
            $validated['created_by'] = $actor->id;
            $validated['updated_by'] = $actor->id;

            $expense = Expense::create($validated);
            $expense->payerAllocations()->createMany($allocations);
            $this->auditLogger->record($actor, 'expense.created', $expense, null, $expense->only([
                'expense_date', 'description', 'amount', 'category_id', 'payment_status',
            ]));

            return $expense->load(['category', 'creator', 'payerAllocations']);
        });
    }

    public function update(Expense $expense, array $validated, User $actor): Expense
    {
        return DB::transaction(function () use ($expense, $validated, $actor): Expense {
            $before = $expense->only([
                'expense_date', 'description', 'amount', 'category_id', 'payment_status',
            ]);
            $allocations = $validated['payer_allocations'] ?? null;
            unset($validated['payer_allocations']);
            $validated['updated_by'] = $actor->id;
            $expense->update($validated);
            if ($allocations !== null) {
                $expense->payerAllocations()->delete();
                $expense->payerAllocations()->createMany($allocations);
            }
            $this->auditLogger->record($actor, 'expense.updated', $expense, $before, $expense->only([
                'expense_date', 'description', 'amount', 'category_id', 'payment_status',
            ]));

            return $expense->load(['category', 'creator', 'payerAllocations']);
        });
    }

    public function delete(Expense $expense, User $actor): void
    {
        DB::transaction(function () use ($expense, $actor): void {
            $before = $expense->only(['description', 'amount', 'expense_date']);
            $expense->delete();
            $this->auditLogger->record($actor, 'expense.deleted', $expense, $before);
        });
    }
}
