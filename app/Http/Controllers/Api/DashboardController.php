<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExpenseResource;
use App\Models\Earning;
use App\Models\Expense;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    public function __invoke(Request $request, ReportService $reports): JsonResponse
    {
        Gate::authorize('viewAny', Expense::class);

        $timezone = config('app.display_timezone');
        $today = Carbon::now($timezone);
        $currentMonth = $today->format('Y-m-01');
        $user = $request->user();
        $selectedYear = $request->integer('year', $today->year);
        $selectedMonth = $request->integer('month', $today->month);
        $baseQuery = Expense::query();
        if (! $user->isSuperAdmin()) {
            $baseQuery->where('created_by', $user->id);
        }
        $monthQuery = (clone $baseQuery)->whereYear('expense_date', $today->year)->whereMonth('expense_date', $today->month);
        $selectedMonthQuery = (clone $baseQuery)->whereYear('expense_date', $selectedYear)->whereMonth('expense_date', $selectedMonth);
        $earningQuery = Earning::query();
        $currentMonthEarnings = (clone $earningQuery)->whereYear('earning_date', $today->year)->whereMonth('earning_date', $today->month);
        $currentYearEarnings = (clone $earningQuery)->whereYear('earning_date', $today->year);
        $selectedMonthEarnings = (clone $earningQuery)->whereYear('earning_date', $selectedYear)->whereMonth('earning_date', $selectedMonth);
        $financial = $user->isSuperAdmin() ? $reports->financialSummary($earningQuery, $baseQuery) : null;

        return response()->json([
            'success' => true,
            'data' => [
                ...$this->dashboardSummary($reports, $baseQuery),
                'financial' => $financial,
                'today' => $this->dashboardSummary($reports, (clone $baseQuery)->whereDate('expense_date', $today->toDateString())),
                'current_month' => $user->isSuperAdmin() ? [...$this->dashboardSummary($reports, (clone $baseQuery)->whereYear('expense_date', $today->year)->whereMonth('expense_date', $today->month)), 'financial' => $reports->financialSummary($currentMonthEarnings, (clone $baseQuery)->whereYear('expense_date', $today->year)->whereMonth('expense_date', $today->month))] : $this->dashboardSummary($reports, (clone $baseQuery)->whereYear('expense_date', $today->year)->whereMonth('expense_date', $today->month)),
                'current_year' => $user->isSuperAdmin() ? [...$this->dashboardSummary($reports, (clone $baseQuery)->whereYear('expense_date', $today->year)), 'financial' => $reports->financialSummary($currentYearEarnings, (clone $baseQuery)->whereYear('expense_date', $today->year))] : $this->dashboardSummary($reports, (clone $baseQuery)->whereYear('expense_date', $today->year)),
                'monthly_trend' => $reports->months($today->year, $user),
                'current_month_categories' => $reports->categories($monthQuery),
                'selected_month' => sprintf('%04d-%02d-01', $selectedYear, $selectedMonth),
                'selected_month_summary' => $user->isSuperAdmin() ? [...$this->dashboardSummary($reports, $selectedMonthQuery), 'financial' => $reports->financialSummary($selectedMonthEarnings, $selectedMonthQuery)] : $this->dashboardSummary($reports, $selectedMonthQuery),
                'selected_month_categories' => $reports->categories($selectedMonthQuery),
                'recent_expenses' => ExpenseResource::collection(
                    (clone $baseQuery)->with(['category', 'creator', 'payerAllocations', 'attachments'])
                        ->orderByDesc('expense_date')->orderByDesc('id')->limit(5)->get()
                )->resolve(),
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
