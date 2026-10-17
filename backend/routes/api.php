<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ItemController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::apiResource('items', ItemController::class);
});
