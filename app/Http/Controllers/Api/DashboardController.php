<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use App\Services\ExpenseSummary;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    public function __invoke(ExpenseSummary $summary, ReportService $reports): JsonResponse
    {
        Gate::authorize('viewAny', Expense::class);

        $timezone = config('app.display_timezone');
        $today = Carbon::now($timezone);
        $currentMonth = $today->format('Y-m-01');
        $monthQuery = Expense::query()->whereDate('period_month', $currentMonth);

        return response()->json([
            'success' => true,
            'data' => [
                ...$summary->forPeriod(),
                'today' => $reports->summary(Expense::query()->whereDate('expense_date', $today->toDateString())),
                'current_month' => $summary->forPeriod($currentMonth),
                'current_year' => $reports->summary(Expense::query()->whereYear('period_month', $today->year)),
                'monthly_trend' => $reports->months($today->year),
                'current_month_categories' => $reports->categories($monthQuery),
                'recent_expenses' => ExpenseResource::collection(
                    Expense::query()->with(['category', 'creator', 'payerAllocations', 'attachments'])
                        ->orderByDesc('expense_date')->orderByDesc('id')->limit(5)->get()
                )->resolve(),
            ],
        ]);
    }
}
