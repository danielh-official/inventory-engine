<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 */
class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'sku' => $this->sku,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->whenLoaded('category', fn () => $this->category?->name),
            'base_unit' => $this->whenLoaded('baseUnit', fn () => $this->baseUnit->code),
            'unit_conversions' => $this->whenLoaded('unitConversions', fn () => $this->unitConversions
                ->mapWithKeys(fn ($conversion) => [$conversion->unit->code => $conversion->base_units_per_unit])),
            'attributes' => $this->getAttribute('attributes') ?? (object) [],
            'is_active' => $this->is_active,
            'on_hand' => $this->whenHas('on_hand', fn () => (int) $this->getAttribute('on_hand')),
        ];
    }
}
