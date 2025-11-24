<?php

use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Health check
Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'Service is running',
        'timestamp' => now()->toIso8601String(),
    ]);
});

// User routes
Route::prefix('users')->group(function () {
    // Standard CRUD operations
    Route::get('/', [UserController::class, 'index']);           // GET /api/users
    Route::post('/', [UserController::class, 'store']);          // POST /api/users
    Route::get('/{id}', [UserController::class, 'show']);        // GET /api/users/{id}
    Route::put('/{id}', [UserController::class, 'update']);      // PUT /api/users/{id}
    Route::patch('/{id}', [UserController::class, 'update']);    // PATCH /api/users/{id}
    Route::delete('/{id}', [UserController::class, 'destroy']);  // DELETE /api/users/{id}

    // Additional routes
    Route::get('/active/list', [UserController::class, 'active']);          // GET /api/users/active/list
    Route::post('/bulk-delete', [UserController::class, 'bulkDelete']);     // POST /api/users/bulk-delete
    Route::get('/stats/overview', [UserController::class, 'statistics']);   // GET /api/users/stats/overview
    Route::post('/check-email', [UserController::class, 'checkEmail']);     // POST /api/users/check-email
});

// Protected routes (uncomment when authentication is set up)
// Route::middleware('auth:sanctum')->group(function () {
//     Route::get('/user', function (Request $request) {
//         return $request->user();
//     });
// });
