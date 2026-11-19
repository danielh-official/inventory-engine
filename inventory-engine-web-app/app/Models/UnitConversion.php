<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['unit_id', 'base_units_per_unit'])]
class UnitConversion extends Model
{
    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<UnitOfMeasure, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class);
    }
}
