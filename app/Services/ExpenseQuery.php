<?php

namespace App\Services;

use App\Models\Expense;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ExpenseQuery
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Expense::query()->with(['category', 'creator', 'payerAllocations']);

        if ($search = $filters['search'] ?? null) {
            $query->where('description', 'like', '%'.$search.'%');
        }
        foreach (['category_id', 'payment_method'] as $field) {
            if (isset($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }
        if (isset($filters['payment_status'])) {
            $filters['payment_status'] === 'unspecified'
                ? $query->whereNull('payment_status')
                : $query->where('payment_status', $filters['payment_status']);
        }
        if (isset($filters['year'])) {
            $query->whereYear('period_month', $filters['year']);
        }
        if (isset($filters['month'])) {
            $query->whereMonth('period_month', $filters['month']);
        }
        if (isset($filters['date_from'])) {
            $query->whereDate('expense_date', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->whereDate('expense_date', '<=', $filters['date_to']);
        }
        if (isset($filters['amount_min'])) {
            $query->where('amount', '>=', $filters['amount_min']);
        }
        if (isset($filters['amount_max'])) {
            $query->where('amount', '<=', $filters['amount_max']);
        }

        return $query->orderBy($filters['sort'] ?? 'expense_date', $filters['direction'] ?? 'desc')
            ->orderByDesc('id')->paginate($filters['per_page'] ?? 20);
    }
}
