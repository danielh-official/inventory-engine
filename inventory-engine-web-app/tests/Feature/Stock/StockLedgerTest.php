<?php

use App\Enums\MovementReason;
use App\Exceptions\InsufficientStock;
use App\Models\Bin;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\StockLedger;

beforeEach(function () {
    $this->ledger = app(StockLedger::class);
    $this->product = Product::factory()->create();
    $this->bin = Bin::factory()->create();
});

test('movements update the level and the ledger together', function () {
    $this->ledger->record($this->product, $this->bin, 10, MovementReason::Adjustment);
    $movement = $this->ledger->record($this->product, $this->bin, -4, MovementReason::Adjustment);

    expect($this->ledger->onHand($this->product, $this->bin))->toBe(6)
        ->and(StockMovement::sum('quantity_delta'))->toBe(6)
        ->and($movement->warehouse_id)->toBe($this->bin->warehouse_id)
        ->and($movement->reason)->toBe(MovementReason::Adjustment);
});

test('stock cannot go below zero and nothing is written when it would', function () {
    $this->ledger->record($this->product, $this->bin, 3, MovementReason::Adjustment);

    expect(fn () => $this->ledger->record($this->product, $this->bin, -5, MovementReason::Adjustment))
        ->toThrow(InsufficientStock::class);

    expect($this->ledger->onHand($this->product, $this->bin))->toBe(3)
        ->and(StockMovement::count())->toBe(1);
});
