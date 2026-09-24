<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAttachmentRequest;
use App\Models\Attachment;
use App\Models\Expense;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    public function store(StoreAttachmentRequest $request, Expense $expense, AuditLogger $auditLogger): JsonResponse
    {
        Gate::authorize('update', $expense);
        $file = $request->file('file');
        $path = $file->store('receipts', 'local');
        $attachment = $expense->attachments()->create([
            'original_name' => $file->getClientOriginalName(),
            'storage_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_by' => $request->user()->id,
        ]);
        $auditLogger->record($request->user(), 'attachment.created', $attachment, null, [
            'expense_id' => $expense->id,
            'original_name' => $attachment->original_name,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $attachment->id, 'original_name' => $attachment->original_name,
                'mime_type' => $attachment->mime_type, 'file_size' => $attachment->file_size,
                'created_at' => $attachment->created_at?->toIso8601String(),
            ],
        ], 201);
    }

    public function show(Expense $expense, Attachment $attachment): StreamedResponse
    {
        Gate::authorize('view', $expense);
        abort_unless($attachment->expense_id === $expense->id, 404);

        return Storage::disk('local')->download($attachment->storage_path, $attachment->original_name);
    }

    public function destroy(Request $request, Expense $expense, Attachment $attachment, AuditLogger $auditLogger): JsonResponse
    {
        Gate::authorize('update', $expense);
        abort_unless($attachment->expense_id === $expense->id, 404);

        $auditLogger->record($request->user(), 'attachment.deleted', $attachment, [
            'expense_id' => $expense->id,
            'original_name' => $attachment->original_name,
        ]);
        Storage::disk('local')->delete($attachment->storage_path);
        $attachment->delete();

        return response()->json(['success' => true, 'message' => 'Attachment deleted successfully.']);
    }
}
