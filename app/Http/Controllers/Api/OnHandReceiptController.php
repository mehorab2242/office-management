<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexOnHandReceiptRequest;
use App\Http\Requests\StoreOnHandReceiptRequest;
use App\Http\Requests\UpdateOnHandReceiptRequest;
use App\Http\Resources\OnHandReceiptResource;
use App\Models\OnHandReceipt;
use App\Services\OnHandBalance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class OnHandReceiptController extends Controller
{
    public function index(IndexOnHandReceiptRequest $request, OnHandBalance $balance): JsonResponse
    {
        $page = OnHandReceipt::query()->where('user_id', $request->user()->id)
            ->when($request->validated('date_from'), fn (Builder $query, string $date): Builder => $query->whereDate('received_date', '>=', $date))
            ->when($request->validated('date_to'), fn (Builder $query, string $date): Builder => $query->whereDate('received_date', '<=', $date))
            ->orderByDesc('received_date')->orderByDesc('id')
            ->paginate($request->integer('per_page', 20));

        return response()->json(['success' => true, 'data' => OnHandReceiptResource::collection($page->getCollection())->resolve(), 'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'per_page' => $page->perPage(), 'total' => $page->total()], 'summary' => $balance->summary($request->user())]);
    }

    public function store(StoreOnHandReceiptRequest $request): JsonResponse
    {
        $receipt = OnHandReceipt::create([...$request->validated(), 'user_id' => $request->user()->id]);

        return response()->json(['success' => true, 'message' => 'Received money recorded successfully.', 'data' => (new OnHandReceiptResource($receipt))->resolve()], 201);
    }

    public function show(OnHandReceipt $onHandReceipt): JsonResponse
    {
        Gate::authorize('view', $onHandReceipt);

        return response()->json(['success' => true, 'data' => (new OnHandReceiptResource($onHandReceipt))->resolve()]);
    }

    public function update(UpdateOnHandReceiptRequest $request, OnHandReceipt $onHandReceipt): JsonResponse
    {
        $onHandReceipt->update($request->validated());

        return response()->json(['success' => true, 'message' => 'Received money updated successfully.', 'data' => (new OnHandReceiptResource($onHandReceipt->fresh()))->resolve()]);
    }

    public function destroy(OnHandReceipt $onHandReceipt): JsonResponse
    {
        Gate::authorize('delete', $onHandReceipt);
        $onHandReceipt->delete();

        return response()->json(['success' => true, 'message' => 'Received money deleted successfully.']);
    }
}
