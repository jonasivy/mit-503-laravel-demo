<?php

use App\Http\Controllers\Api\V1\OrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Design: Versioned API Routes
|--------------------------------------------------------------------------
|
| All routes are grouped under /api/v1/ for API versioning.
| This allows future versions (v2, v3) to coexist without breaking clients.
|
*/

Route::prefix('v1')->group(function () {
    // RESTful order endpoints
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::patch('/orders/{id}', [OrderController::class, 'update']);

    // System Integration: Local webhook test endpoint
    // Receives outbound webhook POST from OrderService and logs the payload
    Route::post('/webhook-test', function (Request $request) {
        Log::channel('notifications')->info('[WEBHOOK RECEIVED] ' . json_encode($request->all()));

        return response()->json(['status' => 'received', 'message' => 'Webhook payload logged']);
    });
});
