<?php

namespace App\Services;

use App\Models\Expense;
use Carbon\CarbonImmutable;

class ExpenseSummary
{
    public function forPeriod(?string $periodMonth = null): array
    {
        $base = Expense::query();
        if ($periodMonth !== null) {
            $nextMonth = CarbonImmutable::parse($periodMonth)->addMonth()->toDateString();
            $base->where('expense_date', '>=', $periodMonth)
                ->where('expense_date', '<', $nextMonth);
        }

        $summary = (clone $base)->selectRaw(
            "COUNT(*) as transaction_count, COALESCE(SUM(amount), 0) as total_amount,
            COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN amount ELSE 0 END), 0) as paid_amount,
            COALESCE(SUM(CASE WHEN payment_status = 'unpaid' THEN amount ELSE 0 END), 0) as unpaid_amount,
            COALESCE(SUM(CASE WHEN payment_status = 'pending' THEN amount ELSE 0 END), 0) as pending_amount,
            COALESCE(SUM(CASE WHEN payment_status IS NULL THEN amount ELSE 0 END), 0) as unspecified_amount"
        )->first();

        return [
            'total_amount' => $this->money($summary->total_amount),
            'paid_amount' => $this->money($summary->paid_amount),
            'unpaid_amount' => $this->money($summary->unpaid_amount),
            'pending_amount' => $this->money($summary->pending_amount),
            'unspecified_amount' => $this->money($summary->unspecified_amount),
            'transaction_count' => (int) $summary->transaction_count,
        ];
    }

    public function categoriesForPeriod(string $periodMonth): array
    {
        $nextMonth = CarbonImmutable::parse($periodMonth)->addMonth()->toDateString();

        return Expense::query()
            ->leftJoin('categories', 'categories.id', '=', 'expenses.category_id')
            ->where('expenses.expense_date', '>=', $periodMonth)
            ->where('expenses.expense_date', '<', $nextMonth)
            ->selectRaw("COALESCE(categories.name, 'Uncategorized') as name, SUM(expenses.amount) as total")
            ->groupBy('categories.id', 'categories.name')
            ->orderBy('name')
            ->get()
            ->map(fn ($row): array => ['name' => $row->name, 'total' => $this->money($row->total)])
            ->all();
    }

    private function money(mixed $value): string
    {
        return bcadd((string) ($value ?? 0), '0', 2);
    }
}
