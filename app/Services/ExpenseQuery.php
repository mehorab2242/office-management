<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ExpenseQuery
{
    public function paginate(array $filters, User $user): LengthAwarePaginator
    {
        return $this->build($filters, $user)->paginate($filters['per_page'] ?? 20);
    }

    public function build(array $filters, User $user): Builder
    {
        $query = Expense::query()->with(['category', 'creator', 'payerAllocations', 'attachments']);
        if (! $user->isSuperAdmin()) {
            $query->where('expenses.created_by', $user->id);
        }

        if ($search = $filters['search'] ?? null) {
            $query->where('expenses.description', 'like', '%'.$search.'%');
        }
        foreach (['category_id', 'payment_method'] as $field) {
            if (isset($filters[$field])) {
                $query->where('expenses.'.$field, $filters[$field]);
            }
        }
        if (isset($filters['payment_status'])) {
            $filters['payment_status'] === 'unspecified'
                ? $query->whereNull('expenses.payment_status')
                : $query->where('expenses.payment_status', $filters['payment_status']);
        }
        if (isset($filters['year'])) {
            $query->whereYear('expenses.expense_date', $filters['year']);
        }
        if (isset($filters['month'])) {
            $query->whereMonth('expenses.expense_date', $filters['month']);
        }
        if (isset($filters['date_from'])) {
            $query->whereDate('expenses.expense_date', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->whereDate('expenses.expense_date', '<=', $filters['date_to']);
        }
        if (isset($filters['amount_min'])) {
            $query->where('expenses.amount', '>=', $filters['amount_min']);
        }
        if (isset($filters['amount_max'])) {
            $query->where('expenses.amount', '<=', $filters['amount_max']);
        }

        return $query->orderBy('expenses.'.($filters['sort'] ?? 'expense_date'), $filters['direction'] ?? 'desc')
            ->orderByDesc('expenses.id');
    }
}
