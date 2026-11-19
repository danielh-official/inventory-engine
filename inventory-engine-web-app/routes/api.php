<?php

use App\Http\Controllers\Api\V1\AdjustmentController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\StockLevelController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->middleware('auth:sanctum')->group(function () {
    Route::get('products', [ProductController::class, 'index'])->name('products.index');
    Route::get('products/{product:sku}', [ProductController::class, 'show'])->name('products.show');
    Route::get('stock-levels', [StockLevelController::class, 'index'])->name('stock-levels.index');

    Route::post('adjustments', [AdjustmentController::class, 'store'])
        ->middleware('abilities:stock:write')
        ->name('adjustments.store');
});
