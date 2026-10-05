<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EarningResource;
use App\Http\Resources\ExpenseResource;
use App\Models\Earning;
use App\Models\Expense;
use App\Models\User;
use App\Services\OnHandBalance;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    public function __invoke(Request $request, ReportService $reports, OnHandBalance $onHandBalance): JsonResponse
    {
        Gate::authorize('viewAny', Expense::class);

        $timezone = config('app.display_timezone');
        $today = Carbon::now($timezone);
        $user = $request->user();
        $isAdmin = $user->isSuperAdmin();
        $request->validate([
            'year' => ['sometimes', 'integer', 'between:2000,2100'],
            'month' => ['sometimes', 'integer', 'between:1,12'],
        ]);
        $selectedYear = $request->integer('year', $today->year);
        $selectedMonth = $request->integer('month', $today->month);
        $baseQuery = Expense::query();
        if (! $user->isSuperAdmin()) {
            $baseQuery->where('created_by', $user->id);
        }
        $selectedMonthQuery = (clone $baseQuery)->whereYear('expense_date', $selectedYear)->whereMonth('expense_date', $selectedMonth);
        $selectedMonthDate = Carbon::createFromDate($selectedYear, $selectedMonth, 1);
        $selectedMonthEarnings = Earning::query()->whereYear('earning_date', $selectedYear)->whereMonth('earning_date', $selectedMonth);
        $selectedMonthFinancial = $isAdmin ? $reports->financialSummary($selectedMonthEarnings, $selectedMonthQuery) : null;
        $previousMonthDate = $selectedMonthDate->copy()->subMonth();
        $previousMonthExpenses = (clone $baseQuery)->whereYear('expense_date', $previousMonthDate->year)->whereMonth('expense_date', $previousMonthDate->month);
        $previousMonthEarnings = Earning::query()->whereYear('earning_date', $previousMonthDate->year)->whereMonth('earning_date', $previousMonthDate->month);
        $recentTransactions = null;

        if ($isAdmin) {
            $recentExpenses = (clone $baseQuery)->with(['category', 'creator', 'payerAllocations', 'attachments'])
                ->orderByDesc('expense_date')->orderByDesc('id')->limit(5)->get();
            $recentEarnings = Earning::query()->with('creator')->orderByDesc('earning_date')->orderByDesc('id')->limit(5)->get();
            $recentTransactions = collect([
                ...ExpenseResource::collection($recentExpenses)->resolve(),
                ...EarningResource::collection($recentEarnings)->resolve(),
            ])->map(function (array $transaction): array {
                $isEarning = array_key_exists('earning_date', $transaction);

                return [
                    'id' => $transaction['id'],
                    'type' => $isEarning ? 'earning' : 'expense',
                    'date' => $transaction[$isEarning ? 'earning_date' : 'expense_date'],
                    'description' => $transaction['description'],
                    'category' => $isEarning ? $transaction['source'] : ($transaction['category']['name'] ?? 'Uncategorized'),
                    'amount' => $transaction['amount'],
                ];
            })->all();
            usort($recentTransactions, fn (array $first, array $second): int => [$second['date'], $second['id']] <=> [$first['date'], $first['id']]);
            $recentTransactions = array_slice($recentTransactions, 0, 8);
        }

        return response()->json([
            'success' => true,
            'data' => [
                ...$this->dashboardSummary($reports, $baseQuery),
                'financial' => $isAdmin ? $reports->financialSummary(Earning::query(), Expense::query()) : null,
                'today' => $this->dashboardSummary($reports, (clone $baseQuery)->whereDate('expense_date', $today->toDateString())),
                'current_month' => $this->dashboardSummary($reports, (clone $baseQuery)->whereYear('expense_date', $today->year)->whereMonth('expense_date', $today->month)),
                'current_year' => $this->dashboardSummary($reports, (clone $baseQuery)->whereYear('expense_date', $today->year)),
                'monthly_trend' => $reports->months($today->year, $user),
                'selected_month' => sprintf('%04d-%02d-01', $selectedYear, $selectedMonth),
                'selected_month_summary' => $isAdmin ? [...$this->dashboardSummary($reports, $selectedMonthQuery), 'financial' => $selectedMonthFinancial] : $this->dashboardSummary($reports, $selectedMonthQuery),
                'previous_month_summary' => $isAdmin ? $reports->financialSummary($previousMonthEarnings, $previousMonthExpenses) : null,
                'selected_month_categories' => $reports->categories($selectedMonthQuery),
                'selected_month_earning_sources' => $isAdmin ? $reports->earningSources($selectedMonthEarnings) : null,
                'selected_month_daily_trend' => $isAdmin ? $reports->dailyFinancialTrend($selectedMonthEarnings, $selectedMonthQuery, $selectedYear, $selectedMonth) : null,
                'recent_transactions' => $recentTransactions,
                'recent_expenses' => $isAdmin ? null : ExpenseResource::collection(
                    (clone $baseQuery)->with(['category', 'creator', 'payerAllocations', 'attachments'])
                        ->orderByDesc('expense_date')->orderByDesc('id')->limit(5)->get()
                )->resolve(),
                'on_hand' => $user->role === User::ROLE_STAFF ? $onHandBalance->summary($user) : null,
            ],
        ]);
    }

    /** @return array{total_amount: string, transaction_count: int, average_amount: string, largest_amount: string} */
    private function dashboardSummary(ReportService $reports, Builder $query): array
    {
        $summary = $reports->summary($query);

        return [
            'total_amount' => $summary['total_amount'],
            'transaction_count' => $summary['transaction_count'],
            'average_amount' => $summary['average_amount'],
            'largest_amount' => $summary['largest_amount'],
        ];
    }
}
