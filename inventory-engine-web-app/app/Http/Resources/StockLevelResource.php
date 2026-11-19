<?php

namespace App\Http\Resources;

use App\Models\StockLevel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin StockLevel
 */
class StockLevelResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'sku' => $this->product->sku,
            'warehouse' => $this->bin->warehouse->code,
            'bin' => $this->bin->code,
            'quantity' => $this->quantity,
            'unit' => $this->product->baseUnit->code,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
