<?php

use App\Http\Controllers\Api\ArticleController;
use Illuminate\Support\Facades\Route;

// Everything here is under /api (added by bootstrap/app.php). Reference: api-usage.md.
Route::prefix('v1')->middleware(['api.key', 'throttle:api'])->group(function () {
    // GET for plain reads; POST too, so a call with mark_read=1 (which changes
    // data) can be sent as POST with a JSON body.
    Route::match(['get', 'post'], '/articles', [ArticleController::class, 'index']);
    Route::post('/fetch-now', [ArticleController::class, 'fetchNow']);
    Route::get('/status', [ArticleController::class, 'status']);
});
