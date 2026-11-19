<?php

use App\Enums\AdjustmentReason;
use App\Models\Adjustment;
use App\Models\Bin;
use App\Models\Product;
use App\Models\User;
use App\Services\StockLedger;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs($this->user = User::factory()->create());
    $this->bin = Bin::factory()->create();
    $this->product = Product::factory()->create();
});

test('the stock page adjusts stock through the ledger', function () {
    $this->get(route('stock.index'))->assertOk();

    Livewire::test('pages::stock.index', ['warehouse' => $this->bin->warehouse_id])
        ->set('sku', $this->product->sku)
        ->set('binId', $this->bin->id)
        ->set('quantity', 15)
        ->set('reason', AdjustmentReason::Found->value)
        ->call('adjust')
        ->assertHasNoErrors()
        ->assertSee($this->product->sku);

    expect(app(StockLedger::class)->onHand($this->product, $this->bin))->toBe(15)
        ->and(Adjustment::sole()->user_id)->toBe($this->user->id);
});

test('the stock page refuses to take stock below zero', function () {
    Livewire::test('pages::stock.index', ['warehouse' => $this->bin->warehouse_id])
        ->set('sku', $this->product->sku)
        ->set('binId', $this->bin->id)
        ->set('quantity', -1)
        ->set('reason', AdjustmentReason::Damaged->value)
        ->call('adjust')
        ->assertHasErrors('quantity');

    expect(Adjustment::count())->toBe(0);
});

test('a bin from another warehouse is rejected', function () {
    $elsewhere = Bin::factory()->create();

    Livewire::test('pages::stock.index', ['warehouse' => $this->bin->warehouse_id])
        ->set('sku', $this->product->sku)
        ->set('binId', $elsewhere->id)
        ->set('quantity', 1)
        ->set('reason', AdjustmentReason::Found->value)
        ->call('adjust')
        ->assertHasErrors('binId');
});
