<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use App\Services\ExpenseQuery;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ReportController extends Controller
{
    public function yearly(Request $request, ReportService $reports): JsonResponse
    {
        Gate::authorize('viewAny', Expense::class);
        $validated = $request->validate(['year' => ['required', 'integer', 'between:2000,2100']]);
        $query = Expense::query()->whereYear('period_month', $validated['year']);

        $summary = $reports->summary($query);

        return response()->json(['success' => true, 'data' => [
            'year' => (int) $validated['year'], 'summary' => $reports->summary($query),
            'average_monthly_amount' => bcdiv($summary['total_amount'], '12', 2),
            'months' => $reports->months((int) $validated['year']), 'categories' => $reports->categories($query),
        ]]);
    }

    public function custom(IndexExpenseRequest $request, ExpenseQuery $expenses, ReportService $reports): JsonResponse
    {
        $query = $expenses->build($request->safe()->except(['page', 'per_page']));
        $page = (clone $query)->paginate($request->integer('per_page', 20));

        return response()->json(['success' => true, 'data' => [
            'summary' => $reports->summary($query), 'categories' => $reports->categories($query),
            'expenses' => ExpenseResource::collection($page->getCollection())->resolve(),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(), 'total' => $page->total()],
        ]]);
    }
}
