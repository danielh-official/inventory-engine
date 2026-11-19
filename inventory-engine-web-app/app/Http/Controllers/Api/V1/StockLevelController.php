<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\StockLevelResource;
use App\Models\StockLevel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StockLevelController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'warehouse' => ['nullable', 'string', 'exists:warehouses,code'],
            'sku' => ['nullable', 'string'],
            'per_page' => ['nullable', 'integer', 'between:1,500'],
        ]);

        $levels = StockLevel::query()
            ->select('stock_levels.*')
            ->join('products', 'products.id', '=', 'stock_levels.product_id')
            ->join('bins', 'bins.id', '=', 'stock_levels.bin_id')
            ->join('warehouses', 'warehouses.id', '=', 'bins.warehouse_id')
            ->where('stock_levels.quantity', '!=', 0)
            ->when($request->filled('warehouse'), fn ($query) => $query->where('warehouses.code', $request->string('warehouse')->toString()))
            ->when($request->filled('sku'), fn ($query) => $query->where('products.sku', $request->string('sku')->toString()))
            ->with(['product.baseUnit', 'bin.warehouse'])
            ->orderBy('warehouses.code')
            ->orderBy('products.sku')
            ->orderBy('bins.code')
            ->paginate($request->integer('per_page', 100))
            ->withQueryString();

        return StockLevelResource::collection($levels);
    }
}
