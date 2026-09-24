<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Services\ExpenseSummary;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    public function __invoke(ExpenseSummary $summary): JsonResponse
    {
        Gate::authorize('viewAny', Expense::class);

        return response()->json([
            'success' => true,
            'data' => [
                ...$summary->forPeriod(),
                'current_month' => $summary->forPeriod(now(config('app.display_timezone'))->format('Y-m-01')),
            ],
        ]);
    }
}
