<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'between:1,200'],
        ]);

        $search = trim($request->string('search')->toString());

        $products = Product::query()
            ->with(['category', 'baseUnit'])
            ->withSum('stockLevels as on_hand', 'quantity')
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('sku', 'ilike', "%{$search}%")
                ->orWhere('name', 'ilike', "%{$search}%")))
            ->orderBy('sku')
            ->paginate($request->integer('per_page', 50))
            ->withQueryString();

        return ProductResource::collection($products);
    }

    public function show(Product $product): ProductResource
    {
        $product->load(['category', 'baseUnit', 'unitConversions.unit'])
            ->loadSum('stockLevels as on_hand', 'quantity');

        return new ProductResource($product);
    }
}
