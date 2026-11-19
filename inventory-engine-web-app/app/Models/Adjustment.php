<?php

namespace App\Models;

use App\Enums\AdjustmentReason;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property AdjustmentReason $reason
 */
#[Fillable(['product_id', 'bin_id', 'quantity_delta', 'reason', 'note', 'user_id', 'stock_movement_id'])]
class Adjustment extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['reason' => AdjustmentReason::class];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<Bin, $this>
     */
    public function bin(): BelongsTo
    {
        return $this->belongsTo(Bin::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<StockMovement, $this>
     */
    public function stockMovement(): BelongsTo
    {
        return $this->belongsTo(StockMovement::class);
    }
}
