<?php

use App\Enums\MovementReason;
use App\Models\Bin;
use App\Models\Product;
use App\Models\User;
use App\Services\StockLedger;
use Livewire\Livewire;

beforeEach(fn () => $this->actingAs(User::factory()->create()));

test('the product list searches by sku and shows on-hand totals', function () {
    $widget = Product::factory()->create(['sku' => 'WID-001', 'name' => 'Widget']);
    $gadget = Product::factory()->create(['sku' => 'GAD-001', 'name' => 'Gadget']);
    app(StockLedger::class)->record($widget, Bin::factory()->create(), 7, MovementReason::Adjustment);
    app(StockLedger::class)->record($widget, Bin::factory()->create(), 5, MovementReason::Adjustment);

    $this->get(route('products.index'))->assertOk();

    Livewire::test('pages::products.index')
        ->set('search', 'wid')
        ->assertSee('WID-001')
        ->assertSee('12')
        ->assertDontSee('GAD-001');
});

test('the product page shows stock by bin and recent movements', function () {
    $product = Product::factory()->create();
    $bin = Bin::factory()->create();
    app(StockLedger::class)->record($product, $bin, 30, MovementReason::Adjustment);
    app(StockLedger::class)->record($product, $bin, -4, MovementReason::Adjustment);

    $this->get(route('products.show', $product))
        ->assertOk()
        ->assertSee($bin->code)
        ->assertSee('26')
        ->assertSeeInOrder(['-4', '+30']);
});
