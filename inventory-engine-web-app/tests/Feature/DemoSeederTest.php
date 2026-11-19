<?php

use App\Models\Product;
use App\Models\StockLevel;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\DB;

test('demo seeder is deterministic', function () {
    $snapshot = fn () => [
        'users' => User::orderBy('id')->get(['name', 'email', 'remember_token', 'created_at'])->toArray(),
        'products' => Product::orderBy('sku')->get(['sku', 'name', 'attributes'])->toArray(),
        'stock' => StockLevel::query()
            ->join('products', 'products.id', '=', 'stock_levels.product_id')
            ->join('bins', 'bins.id', '=', 'stock_levels.bin_id')
            ->orderBy('products.sku')->orderBy('bins.code')->orderBy('bins.warehouse_id')
            ->get(['products.sku', 'bins.code', 'stock_levels.quantity'])
            ->toArray(),
    ];

    $this->seed(DemoSeeder::class);
    $first = $snapshot();

    foreach (['adjustments', 'stock_levels', 'stock_movements', 'unit_conversions', 'products', 'categories', 'units_of_measure', 'bins', 'warehouses', 'users'] as $table) {
        DB::table($table)->delete();
    }
    $this->seed(DemoSeeder::class);

    expect($first['users'])->toHaveCount(10)
        ->and($first['products'])->toHaveCount(200)
        ->and($first['stock'])->not->toBeEmpty()
        ->and($snapshot())->toBe($first)
        ->and($first['users'][0]['created_at'])->toStartWith('2026-01-01');
});

test('demo seeder keeps the ledger and stock levels in agreement', function () {
    $this->seed(DemoSeeder::class);

    expect((int) DB::table('stock_movements')->sum('quantity_delta'))
        ->toBe((int) StockLevel::sum('quantity'))
        ->and(DB::table('adjustments')->count())->toBe(DB::table('stock_movements')->count());
});
