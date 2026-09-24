<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class AuditLogController extends Controller
{
    public function __invoke(): JsonResponse
    {
        Gate::authorize('viewAny', User::class);
        $page = AuditLog::query()->orderByDesc('id')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $page->getCollection()->map(fn (AuditLog $log): array => [
                'id' => $log->id,
                'actor_user_id' => $log->actor_user_id,
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
