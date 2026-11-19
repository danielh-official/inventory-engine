<?php

namespace App\Http\Requests\Api;

use App\Enums\AdjustmentReason;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdjustmentRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sku' => ['required', 'string', Rule::exists('products', 'sku')],
            'warehouse' => ['required', 'string', Rule::exists('warehouses', 'code')],
            'bin' => ['required', 'string'],
            'quantity' => ['required', 'integer', 'not_in:0', 'between:-1000000,1000000'],
            'reason' => ['required', Rule::enum(AdjustmentReason::class)],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
