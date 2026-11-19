<?php

namespace App\Models;

use App\Enums\MovementReason;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property MovementReason $reason
 * @property Carbon $occurred_at
 */
#[Fillable(['product_id', 'warehouse_id', 'bin_id', 'quantity_delta', 'reason', 'user_id', 'occurred_at'])]
class StockMovement extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reason' => MovementReason::class,
            'occurred_at' => 'datetime',
        ];
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
}
