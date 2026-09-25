<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Models\Earning;
use App\Models\User;
use App\Services\ExpenseQuery;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class MonthlyReportController extends Controller
{
    public function __invoke(IndexExpenseRequest $request, ExpenseQuery $expenses, ReportService $reports): JsonResponse
    {
        Gate::authorize('viewAny', User::class);
        $validated = $request->validate([
            'year' => ['required', 'integer', 'between:2000,2100'],
            'month' => ['required', 'integer', 'between:1,12'],
        ]);
        $period = sprintf('%04d-%02d-01', $validated['year'], $validated['month']);
        $filters = $request->safe()->except(['page', 'per_page']);
        $query = $expenses->build($filters, $request->user());
        $earningQuery = Earning::query()->whereYear('earning_date', $validated['year'])->whereMonth('earning_date', $validated['month']);
        $page = (clone $query)->paginate($request->integer('per_page', 15));

        $financial = $reports->financialSummary($earningQuery, $query);

        return response()->json([
            'success' => true,
            'data' => [
                'period' => $period,
                ...$reports->summary($query),
                'financial' => $financial,
                'categories' => $reports->categories($query),
                'expenses' => ExpenseResource::collection($page->getCollection())->resolve(),
                'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(),
                    'per_page' => $page->perPage(), 'total' => $page->total()],
            ],
        ]);
    }
}
