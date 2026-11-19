<?php

namespace App\Actions\Stock;

use App\Enums\AdjustmentReason;
use App\Enums\MovementReason;
use App\Models\Adjustment;
use App\Models\Bin;
use App\Models\Product;
use App\Models\User;
use App\Services\StockLedger;
use Illuminate\Support\Facades\DB;

class RecordAdjustment
{
    public function __construct(private StockLedger $ledger) {}

    public function handle(
        Product $product,
        Bin $bin,
        int $delta,
        AdjustmentReason $reason,
        ?string $note = null,
        ?User $user = null,
    ): Adjustment {
        return DB::transaction(function () use ($product, $bin, $delta, $reason, $note, $user) {
            $movement = $this->ledger->record($product, $bin, $delta, MovementReason::Adjustment, $user);

            return Adjustment::create([
                'product_id' => $product->id,
                'bin_id' => $bin->id,
                'quantity_delta' => $delta,
                'reason' => $reason,
                'note' => $note,
                'user_id' => $user?->id,
                'stock_movement_id' => $movement->id,
            ]);
        });
    }
}
