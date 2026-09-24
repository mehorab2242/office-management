<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Services\ExpenseSummary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MonthlyReportController extends Controller
{
    public function __invoke(Request $request, ExpenseSummary $summary): JsonResponse
    {
        Gate::authorize('viewAny', Expense::class);
        $validated = $request->validate([
            'year' => ['required', 'integer', 'between:2000,2100'],
            'month' => ['required', 'integer', 'between:1,12'],
        ]);
        $period = sprintf('%04d-%02d-01', $validated['year'], $validated['month']);

        return response()->json([
            'success' => true,
            'data' => [
                'period_month' => $period,
                ...$summary->forPeriod($period),
                'categories' => $summary->categoriesForPeriod($period),
            ],
        ]);
    }
}
