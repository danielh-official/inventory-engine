<?php

namespace App\Services;

use App\Enums\MovementReason;
use App\Exceptions\InsufficientStock;
use App\Models\Bin;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * The only writer of stock. Each call locks the product × bin level row, so concurrent
 * movements on the same row queue up instead of losing updates, and the counter and the
 * movement are committed together.
 */
class StockLedger
{
    public function record(
        Product $product,
        Bin $bin,
        int $delta,
        MovementReason $reason,
        ?User $user = null,
        ?CarbonInterface $occurredAt = null,
    ): StockMovement {
        return DB::transaction(function () use ($product, $bin, $delta, $reason, $user, $occurredAt) {
            // Make sure the row exists, then lock it. insertOrIgnore avoids a race on first use.
            StockLevel::query()->insertOrIgnore([
                'product_id' => $product->id,
                'bin_id' => $bin->id,
                'quantity' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $level = StockLevel::query()
                ->where('product_id', $product->id)
                ->where('bin_id', $bin->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($level->quantity + $delta < 0) {
                throw InsufficientStock::for($product->sku, $bin->code, $level->quantity, $delta);
            }

            $level->increment('quantity', $delta);

            return StockMovement::create([
                'product_id' => $product->id,
                'warehouse_id' => $bin->warehouse_id,
                'bin_id' => $bin->id,
                'quantity_delta' => $delta,
                'reason' => $reason,
                'user_id' => $user?->id,
                'occurred_at' => $occurredAt ?? now(),
            ]);
        });
    }

    public function onHand(Product $product, Bin $bin): int
    {
        return (int) StockLevel::query()
            ->where('product_id', $product->id)
            ->where('bin_id', $bin->id)
            ->value('quantity');
    }
}
