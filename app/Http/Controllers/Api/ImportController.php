<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ImportBatch;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\WorkbookImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ImportController extends Controller
{
    public function analyze(Request $request, WorkbookImportService $imports): JsonResponse
    {
        Gate::authorize('create', User::class);
        $data = $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240']]);

        return response()->json(['success' => true, 'data' => $imports->analyze($data['file'], $request->user())], 201);
    }

    public function preview(Request $request, ImportBatch $batch, WorkbookImportService $imports): JsonResponse
    {
        Gate::authorize('create', User::class);
        $data = $request->validate([
            'sheets' => ['required', 'array', 'min:1'], 'sheets.*.name' => ['required', 'string'],
            'sheets.*.header_row' => ['required', 'integer', 'min:1'], 'sheets.*.mapping' => ['required', 'array'],
            'sheets.*.mapping.description' => ['required', 'string'], 'sheets.*.mapping.amount' => ['required', 'string'],
            'sheets.*.mapping.expense_date' => ['required', 'string'],
            'sheets.*.mapping.payer' => ['sometimes', 'nullable', 'string'],
            'sheets.*.mapping.category' => ['sometimes', 'nullable', 'string'],
            'sheets.*.mapping.payment_status' => ['sometimes', 'nullable', 'string'],
            'sheets.*.mapping.payment_method' => ['sometimes', 'nullable', 'string'],
        ]);

        return response()->json(['success' => true, 'data' => $imports->preview($batch, $data)]);
    }

    public function commit(Request $request, ImportBatch $batch, WorkbookImportService $imports, AuditLogger $audit): JsonResponse
    {
        Gate::authorize('create', User::class);
        $data = $request->validate(['category_map' => ['sometimes', 'array']]);

        return response()->json(['success' => true, 'data' => $imports->commit($batch, $data, $request->user(), $audit)]);
    }

    public function show(ImportBatch $batch, WorkbookImportService $imports): JsonResponse
    {
        Gate::authorize('viewAny', User::class);

        return response()->json(['success' => true, 'data' => $imports->result($batch)]);
    }
}
