<?php

use App\Models\Product;
use App\Models\UnitOfMeasure;

test('quantities convert to base units', function () {
    $each = UnitOfMeasure::factory()->create(['code' => 'EA']);
    $case = UnitOfMeasure::factory()->create(['code' => 'CS']);
    $pallet = UnitOfMeasure::factory()->create(['code' => 'PL']);

    $product = Product::factory()->create(['base_unit_id' => $each->id]);
    $product->unitConversions()->create(['unit_id' => $case->id, 'base_units_per_unit' => 12]);

    expect($product->toBaseUnits(5, $each))->toBe(5)
        ->and($product->toBaseUnits(3, $case))->toBe(36)
        ->and(fn () => $product->toBaseUnits(1, $pallet))->toThrow(InvalidArgumentException::class);
});
