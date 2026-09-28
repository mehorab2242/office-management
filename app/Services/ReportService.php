<?php

namespace App\Services;

use App\Models\Earning;
use App\Models\Expense;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class ReportService
{
    public function financialSummary(Builder $earnings, Builder $expenses): array
    {
        $revenue = $this->money((clone $earnings)->reorder()->sum('amount'));
        $cost = $this->money((clone $expenses)->reorder()->sum('amount'));
        $profit = bcsub($revenue, $cost, 2);
        $margin = bccomp($revenue, '0.00', 2) === 0 ? '0.00' : bcmul(bcdiv($profit, $revenue, 6), '100', 2);

        return ['total_revenue' => $revenue, 'total_cost' => $cost, 'net_profit' => $profit, 'profit_margin' => $margin, 'is_loss' => bccomp($profit, '0.00', 2) < 0, 'transaction_count' => (clone $earnings)->reorder()->count() + (clone $expenses)->reorder()->count()];
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

    public function earningSources(Builder $query): array
    {
        return (clone $query)->reorder()
            ->selectRaw("COALESCE(NULLIF(source, ''), 'Uncategorized') as name, SUM(amount) as total")
            ->groupByRaw("COALESCE(NULLIF(source, ''), 'Uncategorized')")->orderByDesc('total')->get()
            ->map(fn ($row): array => ['name' => $row->name, 'total' => $this->money($row->total)])->all();
    }

    public function dailyFinancialTrend(Builder $earnings, Builder $expenses, int $year, int $month): array
    {
        $start = CarbonImmutable::create($year, $month, 1)->startOfMonth();
        $end = $start->endOfMonth();
        $dailyEarnings = (clone $earnings)->reorder()->whereBetween('earning_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('DATE(earning_date) as day, SUM(amount) as total')->groupBy('day')->get()->keyBy('day');
        $dailyExpenses = (clone $expenses)->reorder()->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('DATE(expense_date) as day, SUM(amount) as total')->groupBy('day')->get()->keyBy('day');

        return collect(range(1, $end->day))->map(function (int $day) use ($start, $dailyEarnings, $dailyExpenses): array {
            $date = $start->setDay($day)->toDateString();

            return ['date' => $date, 'revenue' => $this->money($dailyEarnings->get($date)?->total), 'cost' => $this->money($dailyExpenses->get($date)?->total)];
        })->all();
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

        $includeFinancials = $user === null || $user->isSuperAdmin();
        $earningTotals = $includeFinancials
            ? Earning::query()->whereYear('earning_date', $year)->selectRaw("{$earningExpression} as month, SUM(amount) as total, COUNT(*) as transaction_count")->groupByRaw($earningExpression)->get()->keyBy('month')
            : collect();

        return collect(range(1, 12))->map(function (int $month) use ($totals, $earningTotals, $includeFinancials): array {
            $row = $totals->get($month);
            $earning = $earningTotals->get($month);
            $cost = $this->money($row?->total);

            $result = ['month' => $month, 'total' => $cost, 'cost' => $cost, 'transaction_count' => (int) ($row?->transaction_count ?? 0)];
            if ($includeFinancials) {
                $revenue = $this->money($earning?->total);
                $result['revenue'] = $revenue;
                $result['net_profit'] = bcsub($revenue, $cost, 2);
                $result['transaction_count'] += (int) ($earning?->transaction_count ?? 0);
            }

            return $result;
        })->all();
    }

    private function money(mixed $value): string
    {
        return bcadd((string) ($value ?? 0), '0', 2);
    }
}
