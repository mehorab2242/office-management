<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AuditLogController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', User::class);
        $validated = $request->validate([
            'action' => ['sometimes', 'string', 'max:60'],
            'actor_user_id' => ['sometimes', 'integer', 'exists:users,id'],
            'subject_type' => ['sometimes', 'string', 'max:100'],
            'date_from' => ['sometimes', 'date_format:Y-m-d'],
            'date_to' => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);
        $query = AuditLog::query()->with('actor')->orderByDesc('id');
        if (isset($validated['action'])) {
            $query->where('action', $validated['action']);
        }
        if (isset($validated['actor_user_id'])) {
            $query->where('actor_user_id', $validated['actor_user_id']);
        }
        if (isset($validated['subject_type'])) {
            $query->where('subject_type', $validated['subject_type']);
        }
        if (isset($validated['date_from'])) {
            $query->whereDate('created_at', '>=', $validated['date_from']);
        }
        if (isset($validated['date_to'])) {
            $query->whereDate('created_at', '<=', $validated['date_to']);
        }
        $page = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $page->getCollection()->map(fn (AuditLog $log): array => [
                'id' => $log->id,
                'actor_user_id' => $log->actor_user_id,
                'actor' => $log->actor ? ['id' => $log->actor->id, 'name' => $log->actor->name] : null,
                'action' => $log->action,
                'subject_type' => $log->subject_type,
                'subject_id' => $log->subject_id,
                'old_values' => $log->old_values,
                'new_values' => $log->new_values,
                'created_at' => $log->created_at?->toIso8601String(),
            ])->all(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }
}
