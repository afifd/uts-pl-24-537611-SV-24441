<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $items = Item::latest()->get();

        return response()->json([
            'data' => $items,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'amount' => ['required', 'integer', 'min:0'],
        ]);

        $item = Item::create($validated);

        AuditLogger::info('Item persisted successfully', [
            'who' => [
                'actor' => auth()->id() ?? 'unauthenticated',
                'client_ip' => $request->ip(),
                'user_agent' => $request->userAgent() ?? 'unknown',
            ],
            'what' => [
                'action' => 'create_item',
                'resource' => 'Item',
                'resource_id' => $item->id,
                'title' => $item->title,
                'amount' => $item->amount,
            ],
            'why' => [
                'reason' => 'User submitted valid item payload for persistence',
            ],
        ]);

        return response()->json([
            'message' => 'Item created successfully.',
            'data' => $item,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Item $item): JsonResponse
    {
        return response()->json([
            'data' => $item,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Item $item): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'amount' => ['sometimes', 'required', 'integer', 'min:0'],
        ]);

        $item->update($validated);

        AuditLogger::info('Item updated successfully', [
            'who' => [
                'actor' => auth()->id() ?? 'unauthenticated',
                'client_ip' => $request->ip(),
                'user_agent' => $request->userAgent() ?? 'unknown',
            ],
            'what' => [
                'action' => 'update_item',
                'resource' => 'Item',
                'resource_id' => $item->id,
                'updated_fields' => array_keys($validated),
            ],
            'why' => [
                'reason' => 'User submitted valid item payload for update',
            ],
        ]);

        return response()->json([
            'message' => 'Item updated successfully.',
            'data' => $item->fresh(),
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Item $item): JsonResponse
    {
        $itemId = $item->id;
        $item->delete();

        AuditLogger::info('Item deleted successfully', [
            'who' => [
                'actor' => auth()->id() ?? 'unauthenticated',
                'client_ip' => $request->ip(),
                'user_agent' => $request->userAgent() ?? 'unknown',
            ],
            'what' => [
                'action' => 'delete_item',
                'resource' => 'Item',
                'resource_id' => $itemId,
            ],
            'why' => [
                'reason' => 'User requested item removal',
            ],
        ]);

        return response()->json([
            'message' => 'Item deleted successfully.',
        ], 200);
    }
}
