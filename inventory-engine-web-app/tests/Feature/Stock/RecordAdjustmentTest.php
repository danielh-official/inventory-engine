<?php

use App\Actions\Stock\RecordAdjustment;
use App\Enums\AdjustmentReason;
use App\Exceptions\InsufficientStock;
use App\Models\Adjustment;
use App\Models\Bin;
use App\Models\Product;
use App\Models\User;
use App\Services\StockLedger;

test('an adjustment records its movement and moves stock', function () {
    $product = Product::factory()->create();
    $bin = Bin::factory()->create();
    $user = User::factory()->create();

    $adjustment = app(RecordAdjustment::class)->handle($product, $bin, 12, AdjustmentReason::Found, 'Behind the rack', $user);

    expect($adjustment->stockMovement->quantity_delta)->toBe(12)
        ->and($adjustment->stockMovement->user_id)->toBe($user->id)
        ->and($adjustment->reason)->toBe(AdjustmentReason::Found)
        ->and(app(StockLedger::class)->onHand($product, $bin))->toBe(12);
});

test('a rejected adjustment leaves no adjustment behind', function () {
    $product = Product::factory()->create();
    $bin = Bin::factory()->create();

    expect(fn () => app(RecordAdjustment::class)->handle($product, $bin, -1, AdjustmentReason::Damaged))
        ->toThrow(InsufficientStock::class);

    expect(Adjustment::count())->toBe(0);
});
