<?php

use App\Http\Controllers\Api\ImportController;
use Illuminate\Support\Facades\Route;

Route::prefix('imports')->group(function () {
    Route::get('/', [ImportController::class, 'index']);
    Route::post('/', [ImportController::class, 'store']);
    Route::get('/{id}', [ImportController::class, 'show']);
});
