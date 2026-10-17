<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
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
        $item->delete();

        return response()->json([
            'message' => 'Item deleted successfully.',
        ], 200);
    }
}
