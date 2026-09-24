<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ReportService
{
    public function summary(Builder $query): array
    {
        $row = (clone $query)->reorder()->selectRaw(
            'COUNT(*) as transaction_count, COALESCE(SUM(amount), 0) as total_amount, COALESCE(MAX(amount), 0) as largest_amount'
        )->first();
        $count = (int) $row->transaction_count;
        $total = $this->money($row->total_amount);

        return ['total_amount' => $total, 'transaction_count' => $count,
            'average_amount' => $count > 0 ? $this->money(bcdiv($total, (string) $count, 2)) : '0.00',
            'largest_amount' => $this->money($row->largest_amount)];
    }

    public function categories(Builder $query): array
    {
        return (clone $query)->reorder()->getQuery()->leftJoin('categories', 'categories.id', 'expenses.category_id')
            ->selectRaw("COALESCE(categories.name, 'Uncategorized') as name, SUM(expenses.amount) as total")
            ->groupBy('categories.id', 'categories.name')->orderByDesc('total')->get()
            ->map(fn ($row): array => ['name' => $row->name, 'total' => $this->money($row->total)])->all();
    }

    public function months(int $year, ?User $user = null): array
    {
        $expression = Expense::query()->getConnection()->getDriverName() === 'sqlite'
            ? "CAST(strftime('%m', expense_date) AS INTEGER)" : 'MONTH(expense_date)';
        $query = Expense::query()->whereYear('expense_date', $year);
        if ($user && ! $user->isSuperAdmin()) {
            $query->where('created_by', $user->id);
        }
        $totals = $query
            ->selectRaw("{$expression} as month, SUM(amount) as total, COUNT(*) as transaction_count")
            ->groupByRaw($expression)->get()->keyBy('month');

        return collect(range(1, 12))->map(function (int $month) use ($totals): array {
            $row = $totals->get($month);

            return ['month' => $month, 'total' => $this->money($row?->total), 'transaction_count' => (int) ($row?->transaction_count ?? 0)];
        })->all();
    }

    private function money(mixed $value): string
    {
        return bcadd((string) ($value ?? 0), '0', 2);
    }
}
