<?php

use App\Http\Controllers\Api\V1\OrderController;
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
});
