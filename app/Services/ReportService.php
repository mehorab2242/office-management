<?php

namespace App\Services;

use App\Models\Expense;
use Illuminate\Database\Eloquent\Builder;

class ReportService
{
    public function summary(Builder $query): array
    {
        $row = (clone $query)->reorder()->selectRaw(
            "COUNT(*) as transaction_count, COALESCE(SUM(amount), 0) as total_amount,
            COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN amount ELSE 0 END), 0) as paid_amount,
            COALESCE(SUM(CASE WHEN payment_status = 'unpaid' THEN amount ELSE 0 END), 0) as unpaid_amount,
            COALESCE(SUM(CASE WHEN payment_status = 'pending' THEN amount ELSE 0 END), 0) as pending_amount,
            COALESCE(SUM(CASE WHEN payment_status IS NULL THEN amount ELSE 0 END), 0) as unspecified_amount"
        )->first();
        $count = (int) $row->transaction_count;
        $total = $this->money($row->total_amount);

        return ['total_amount' => $total, 'paid_amount' => $this->money($row->paid_amount),
            'unpaid_amount' => $this->money($row->unpaid_amount), 'pending_amount' => $this->money($row->pending_amount),
            'unspecified_amount' => $this->money($row->unspecified_amount), 'transaction_count' => $count,
            'average_amount' => $count > 0 ? $this->money(bcdiv($total, (string) $count, 2)) : '0.00'];
    }

    public function categories(Builder $query): array
    {
        return (clone $query)->reorder()->getQuery()->leftJoin('categories', 'categories.id', 'expenses.category_id')
            ->selectRaw("COALESCE(categories.name, 'Uncategorized') as name, SUM(expenses.amount) as total")
            ->groupBy('categories.id', 'categories.name')->orderByDesc('total')->get()
            ->map(fn ($row): array => ['name' => $row->name, 'total' => $this->money($row->total)])->all();
    }

    public function months(int $year): array
    {
        $expression = Expense::query()->getConnection()->getDriverName() === 'sqlite'
            ? "CAST(strftime('%m', period_month) AS INTEGER)" : 'MONTH(period_month)';
        $totals = Expense::query()->whereYear('period_month', $year)
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
