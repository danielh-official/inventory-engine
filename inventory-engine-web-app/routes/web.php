<?php

use App\Http\Controllers\StockController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::get('stock', [StockController::class, 'index'])->name('stock.index');
    Route::post('stock/adjust', [StockController::class, 'adjust'])->name('stock.adjust');
    Route::get('stock/hazmat', [StockController::class, 'hazmat'])->name('stock.hazmat');
    Route::get('stock/export', [StockController::class, 'export'])->name('stock.export');
});

require __DIR__.'/settings.php';
