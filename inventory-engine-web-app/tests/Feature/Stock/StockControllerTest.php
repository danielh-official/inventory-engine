<?php

use App\Models\Bin;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->actingAs($this->user = User::factory()->create());
    $this->bin = Bin::factory()->create();
    $this->product = Product::factory()->create();
});

test('adjusting creates then increments the stock level and logs each movement', function () {
    $adjust = fn (int $qty) => $this->post(route('stock.adjust'), [
        'product_id' => $this->product->id,
        'bin_id' => $this->bin->id,
        'quantity' => $qty,
    ]);

    $adjust(10)->assertRedirect(route('stock.index', ['warehouse' => $this->bin->warehouse_id]));
    $adjust(-3);

    expect(DB::table('stock_levels')->where('product_id', $this->product->id)->value('quantity'))->toBe(7)
        ->and(DB::table('stock_movements')->orderBy('id')->pluck('quantity_delta')->all())->toBe([10, -3])
        ->and(DB::table('stock_movements')->value('user_id'))->toBe($this->user->id);
});

test('adjusting rejects a zero quantity', function () {
    $this->post(route('stock.adjust'), [
        'product_id' => $this->product->id,
        'bin_id' => $this->bin->id,
        'quantity' => 0,
    ])->assertSessionHasErrors('quantity');

    expect(DB::table('stock_movements')->count())->toBe(0);
});

test('the stock page lists levels in the chosen warehouse', function () {
    DB::table('stock_levels')->insert(['product_id' => $this->product->id, 'bin_id' => $this->bin->id, 'quantity' => 42]);

    $this->get(route('stock.index', ['warehouse' => $this->bin->warehouse_id]))
        ->assertOk()
        ->assertSee($this->product->sku)
        ->assertSee($this->bin->code);
});

test('the hazmat report only lists hazmat products on hand', function () {
    $hazmat = Product::factory()->hazmat()->create();
    $safe = Product::factory()->create(['attributes' => ['hazmat' => false]]);

    DB::table('stock_levels')->insert([
        ['product_id' => $hazmat->id, 'bin_id' => $this->bin->id, 'quantity' => 5],
        ['product_id' => $safe->id, 'bin_id' => $this->bin->id, 'quantity' => 5],
    ]);

    $this->get(route('stock.hazmat'))
        ->assertOk()
        ->assertSee($hazmat->sku)
        ->assertDontSee($safe->sku);
});

test('export streams the warehouse stock as CSV', function () {
    DB::table('stock_levels')->insert(['product_id' => $this->product->id, 'bin_id' => $this->bin->id, 'quantity' => 9]);

    $csv = $this->get(route('stock.export', ['warehouse' => $this->bin->warehouse_id]))
        ->assertOk()
        ->streamedContent();

    expect($csv)->toBe("sku,name,bin,quantity\n{$this->product->sku},\"{$this->product->name}\",{$this->bin->code},9\n");
});
