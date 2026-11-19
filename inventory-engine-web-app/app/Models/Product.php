<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

#[Fillable(['sku', 'name', 'description', 'category_id', 'base_unit_id', 'attributes', 'is_active'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attributes' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<UnitOfMeasure, $this>
     */
    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'base_unit_id');
    }

    /**
     * @return HasMany<UnitConversion, $this>
     */
    public function unitConversions(): HasMany
    {
        return $this->hasMany(UnitConversion::class);
    }

    /**
     * Convert a quantity in any of this product's units to base units.
     */
    public function toBaseUnits(int $quantity, UnitOfMeasure $unit): int
    {
        if ($unit->id === $this->base_unit_id) {
            return $quantity;
        }

        $conversion = $this->unitConversions->firstWhere('unit_id', $unit->id)
            ?? throw new InvalidArgumentException("{$this->sku} has no conversion for unit {$unit->code}.");

        return $quantity * $conversion->base_units_per_unit;
    }
}
