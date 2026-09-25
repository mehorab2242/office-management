<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexEarningRequest;
use App\Http\Requests\StoreEarningRequest;
use App\Http\Requests\UpdateEarningRequest;
use App\Http\Resources\EarningResource;
use App\Models\Earning;
use App\Services\EarningQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EarningController extends Controller
{
    public function index(IndexEarningRequest $request, EarningQuery $query): JsonResponse
    {
        $page = $query->build($request->validated())->paginate($request->integer('per_page', 20));

        return response()->json(['success' => true, 'data' => EarningResource::collection($page->getCollection())->resolve(), 'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'per_page' => $page->perPage(), 'total' => $page->total()]]);
    }

    public function store(StoreEarningRequest $request): JsonResponse
    {
        $earning = Earning::create([...$request->validated(), 'created_by' => $request->user()->id, 'updated_by' => $request->user()->id]);

        return response()->json(['success' => true, 'message' => 'Earning created successfully.', 'data' => (new EarningResource($earning->load('creator')))->resolve()], 201);
    }

    public function show(Earning $earning): JsonResponse
    {
        Gate::authorize('view', $earning);

        return response()->json(['success' => true, 'data' => (new EarningResource($earning->load('creator')))->resolve()]);
    }

    public function update(UpdateEarningRequest $request, Earning $earning): JsonResponse
    {
        $earning->update([...$request->validated(), 'updated_by' => $request->user()->id]);

        return response()->json(['success' => true, 'message' => 'Earning updated successfully.', 'data' => (new EarningResource($earning->fresh()->load('creator')))->resolve()]);
    }

    public function destroy(Request $request, Earning $earning): JsonResponse
    {
        Gate::authorize('delete', $earning);
        $earning->delete();

        return response()->json(['success' => true, 'message' => 'Earning deleted successfully.']);
    }
}
