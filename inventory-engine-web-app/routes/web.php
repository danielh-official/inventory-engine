<?php

use App\Http\Controllers\LegacyStockController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::livewire('products', 'pages::products.index')->name('products.index');
    Route::livewire('products/{product}', 'pages::products.show')->name('products.show');

    Route::prefix('legacy/stock')->name('legacy.stock.')->controller(LegacyStockController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('adjust', 'adjust')->name('adjust');
        Route::get('hazmat', 'hazmat')->name('hazmat');
        Route::get('export', 'export')->name('export');
    });
});

require __DIR__.'/settings.php';
