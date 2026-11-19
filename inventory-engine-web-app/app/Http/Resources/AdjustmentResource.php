<?php

namespace App\Http\Resources;

use App\Models\Adjustment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Adjustment
 */
class AdjustmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->product->sku,
            'warehouse' => $this->bin->warehouse->code,
            'bin' => $this->bin->code,
            'quantity' => $this->quantity_delta,
            'reason' => $this->reason->value,
            'note' => $this->note,
            'movement_id' => $this->stock_movement_id,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
