<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Stock\RecordAdjustment;
use App\Enums\AdjustmentReason;
use App\Exceptions\InsufficientStock;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreAdjustmentRequest;
use App\Http\Resources\AdjustmentResource;
use App\Models\Bin;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class AdjustmentController extends Controller
{
    public function store(StoreAdjustmentRequest $request, RecordAdjustment $recordAdjustment): JsonResponse
    {
        $bin = Bin::query()
            ->whereRelation('warehouse', 'code', $request->string('warehouse')->toString())
            ->where('code', $request->string('bin')->toString())
            ->first() ?? throw ValidationException::withMessages(['bin' => __('No such bin in this warehouse.')]);

        try {
            $adjustment = $recordAdjustment->handle(
                Product::query()->where('sku', $request->string('sku')->toString())->firstOrFail(),
                $bin,
                $request->integer('quantity'),
                AdjustmentReason::from($request->string('reason')->toString()),
                $request->input('note'),
                $request->user(),
            );
        } catch (InsufficientStock $e) {
            throw ValidationException::withMessages(['quantity' => $e->getMessage()]);
        }

        return (new AdjustmentResource($adjustment->load(['product', 'bin.warehouse'])))
            ->response()
            ->setStatusCode(201);
    }
}
