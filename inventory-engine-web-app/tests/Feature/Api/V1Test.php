<?php

use App\Enums\MovementReason;
use App\Models\Bin;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockLedger;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->warehouse = Warehouse::factory()->create(['code' => 'WH-1']);
    $this->bin = Bin::factory()->for($this->warehouse)->create(['code' => 'A01-01-1']);
    $this->product = Product::factory()->create(['sku' => 'SKU-1']);
    app(StockLedger::class)->record($this->product, $this->bin, 20, MovementReason::Adjustment);
});

test('the api requires a token', function () {
    $this->getJson('/api/v1/products')->assertUnauthorized();
});

test('products list with on-hand totals and show by sku', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/v1/products?search=sku-1')
        ->assertOk()
        ->assertJsonPath('data.0.sku', 'SKU-1')
        ->assertJsonPath('data.0.on_hand', 20)
        ->assertJsonPath('meta.total', 1);

    $this->getJson('/api/v1/products/SKU-1')
        ->assertOk()
        ->assertJsonPath('data.sku', 'SKU-1')
        ->assertJsonPath('data.on_hand', 20);
});

test('stock levels filter by warehouse and sku', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/v1/stock-levels?warehouse=WH-1&sku=SKU-1')
        ->assertOk()
        ->assertJsonPath('data.0', [
            'sku' => 'SKU-1',
            'warehouse' => 'WH-1',
            'bin' => 'A01-01-1',
            'quantity' => 20,
            'unit' => $this->product->baseUnit->code,
            'updated_at' => $this->product->stockLevels()->first()->updated_at->toIso8601String(),
        ]);
});

test('posting an adjustment needs the stock:write ability', function () {
    $payload = ['sku' => 'SKU-1', 'warehouse' => 'WH-1', 'bin' => 'A01-01-1', 'quantity' => -5, 'reason' => 'damaged'];

    Sanctum::actingAs(User::factory()->create(), ['stock:read']);
    $this->postJson('/api/v1/adjustments', $payload)->assertForbidden();

    Sanctum::actingAs(User::factory()->create(), ['stock:write']);
    $this->postJson('/api/v1/adjustments', $payload)
        ->assertCreated()
        ->assertJsonPath('data.quantity', -5)
        ->assertJsonPath('data.reason', 'damaged');

    expect(app(StockLedger::class)->onHand($this->product, $this->bin))->toBe(15);
});

test('adjustments are rejected for unknown bins and for insufficient stock', function () {
    Sanctum::actingAs(User::factory()->create(), ['stock:write']);
    $payload = ['sku' => 'SKU-1', 'warehouse' => 'WH-1', 'bin' => 'A01-01-1', 'reason' => 'damaged'];

    $this->postJson('/api/v1/adjustments', [...$payload, 'bin' => 'NOPE', 'quantity' => 1])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('bin');

    $this->postJson('/api/v1/adjustments', [...$payload, 'quantity' => -21])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('quantity');
});
