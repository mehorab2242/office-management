<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexExpenseRequest;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use App\Services\ExpenseQuery;
use App\Services\ExpenseWriter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ExpenseController extends Controller
{
    public function index(IndexExpenseRequest $request, ExpenseQuery $query): JsonResponse
    {
        $page = $query->paginate($request->validated());

        return response()->json([
            'success' => true,
            'data' => ExpenseResource::collection($page->getCollection())->resolve(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    public function store(StoreExpenseRequest $request, ExpenseWriter $writer): JsonResponse
    {
        $expense = $writer->create($request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Expense created successfully.',
            'data' => (new ExpenseResource($expense))->resolve(),
        ], 201);
    }

    public function show(Expense $expense): JsonResponse
    {
        Gate::authorize('view', $expense);

        return response()->json([
            'success' => true,
            'data' => (new ExpenseResource($expense->load(['category', 'creator', 'payerAllocations'])))->resolve(),
        ]);
    }

    public function update(UpdateExpenseRequest $request, Expense $expense, ExpenseWriter $writer): JsonResponse
    {
        $expense = $writer->update($expense, $request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Expense updated successfully.',
            'data' => (new ExpenseResource($expense))->resolve(),
        ]);
    }

    public function destroy(Request $request, Expense $expense, ExpenseWriter $writer): JsonResponse
    {
        Gate::authorize('delete', $expense);
        $writer->delete($expense, $request->user());

        return response()->json(['success' => true, 'message' => 'Expense deleted successfully.']);
    }
}
