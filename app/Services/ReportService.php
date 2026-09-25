<?php

namespace App\Services;

use App\Models\Earning;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ReportService
{
    public function financialSummary(Builder $earnings, Builder $expenses): array
    {
        $revenue = $this->money((clone $earnings)->reorder()->sum('amount'));
        $cost = $this->money((clone $expenses)->reorder()->sum('amount'));
        $profit = bcsub($revenue, $cost, 2);

        return ['total_revenue' => $revenue, 'total_cost' => $cost, 'net_profit' => $profit, 'is_loss' => bccomp($profit, '0.00', 2) < 0, 'transaction_count' => (clone $earnings)->reorder()->count() + (clone $expenses)->reorder()->count()];
    }

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
        $expenseExpression = Expense::query()->getConnection()->getDriverName() === 'sqlite'
            ? "CAST(strftime('%m', expense_date) AS INTEGER)" : 'MONTH(expense_date)';
        $earningExpression = Earning::query()->getConnection()->getDriverName() === 'sqlite'
            ? "CAST(strftime('%m', earning_date) AS INTEGER)" : 'MONTH(earning_date)';
        $query = Expense::query()->whereYear('expense_date', $year);
        if ($user && ! $user->isSuperAdmin()) {
            $query->where('created_by', $user->id);
        }
        $totals = $query
            ->selectRaw("{$expenseExpression} as month, SUM(amount) as total, COUNT(*) as transaction_count")
            ->groupByRaw($expenseExpression)->get()->keyBy('month');

        $earningTotals = Earning::query()->whereYear('earning_date', $year)->selectRaw("{$earningExpression} as month, SUM(amount) as total, COUNT(*) as transaction_count")->groupByRaw($earningExpression)->get()->keyBy('month');

        return collect(range(1, 12))->map(function (int $month) use ($totals, $earningTotals): array {
            $row = $totals->get($month);
            $earning = $earningTotals->get($month);
            $cost = $this->money($row?->total);
            $revenue = $this->money($earning?->total);

            return ['month' => $month, 'total' => $cost, 'revenue' => $revenue, 'cost' => $cost, 'net_profit' => bcsub($revenue, $cost, 2), 'transaction_count' => (int) ($row?->transaction_count ?? 0) + (int) ($earning?->transaction_count ?? 0)];
        })->all();
    }

    private function money(mixed $value): string
    {
        return bcadd((string) ($value ?? 0), '0', 2);
    }
}
