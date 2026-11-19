<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * v0 stock screens, kept for the hazmat report and CSV export until those move to
 * the Livewire pages. New stock writes go through App\Services\StockLedger.
 */
class LegacyStockController extends Controller
{
    public function index(Request $request): View
    {
        $warehouses = DB::table('warehouses')->orderBy('code')->get();
        $warehouseId = $request->integer('warehouse') ?: $warehouses->first()?->id;
        $search = trim((string) $request->query('q', ''));

        $query = DB::table('stock_levels')
            ->join('products', 'products.id', '=', 'stock_levels.product_id')
            ->join('bins', 'bins.id', '=', 'stock_levels.bin_id')
            ->join('units_of_measure', 'units_of_measure.id', '=', 'products.base_unit_id')
            ->where('bins.warehouse_id', $warehouseId)
            ->where('stock_levels.quantity', '!=', 0)
            ->select(
                'stock_levels.id',
                'stock_levels.quantity',
                'products.id as product_id',
                'products.sku',
                'products.name as product_name',
                'bins.id as bin_id',
                'bins.code as bin_code',
                'units_of_measure.code as unit_code',
            );

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('products.sku', 'ilike', '%'.$search.'%')
                    ->orWhere('products.name', 'ilike', '%'.$search.'%')
                    ->orWhere('bins.code', 'ilike', '%'.$search.'%');
            });
        }

        $rows = $query->orderBy('products.sku')->orderBy('bins.code')->paginate(50)->withQueryString();

        $bins = DB::table('bins')->where('warehouse_id', $warehouseId)->orderBy('code')->get();
        $products = DB::table('products')->where('is_active', true)->orderBy('sku')->limit(500)->get(['id', 'sku', 'name']);

        $total = DB::table('stock_levels')
            ->join('bins', 'bins.id', '=', 'stock_levels.bin_id')
            ->where('bins.warehouse_id', $warehouseId)
            ->sum('stock_levels.quantity');

        return view('legacy.stock.index', [
            'warehouses' => $warehouses,
            'warehouseId' => $warehouseId,
            'search' => $search,
            'rows' => $rows,
            'bins' => $bins,
            'products' => $products,
            'total' => $total,
        ]);
    }

    public function adjust(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'bin_id' => ['required', 'integer', 'exists:bins,id'],
            'quantity' => ['required', 'integer', 'not_in:0', 'between:-1000000,1000000'],
        ]);

        $bin = DB::table('bins')->where('id', $data['bin_id'])->first();

        DB::transaction(function () use ($data, $bin, $request) {
            $level = DB::table('stock_levels')
                ->where('product_id', $data['product_id'])
                ->where('bin_id', $data['bin_id'])
                ->first();

            if ($level === null) {
                DB::table('stock_levels')->insert([
                    'product_id' => $data['product_id'],
                    'bin_id' => $data['bin_id'],
                    'quantity' => $data['quantity'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('stock_levels')->where('id', $level->id)->increment('quantity', $data['quantity'], [
                    'updated_at' => now(),
                ]);
            }

            DB::table('stock_movements')->insert([
                'product_id' => $data['product_id'],
                'warehouse_id' => $bin->warehouse_id,
                'bin_id' => $data['bin_id'],
                'quantity_delta' => $data['quantity'],
                'reason' => 'adjustment',
                'user_id' => $request->user()?->id,
                'occurred_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return redirect()
            ->route('legacy.stock.index', ['warehouse' => $bin->warehouse_id])
            ->with('status', 'Stock adjusted.');
    }

    public function hazmat(): View
    {
        // Hazmat products that are on hand anywhere, for the weekly safety report.
        $rows = DB::table('products')
            ->join('stock_levels', 'stock_levels.product_id', '=', 'products.id')
            ->join('bins', 'bins.id', '=', 'stock_levels.bin_id')
            ->join('warehouses', 'warehouses.id', '=', 'bins.warehouse_id')
            ->whereRaw("products.attributes::text like '%\"hazmat\":true%'")
            ->where('stock_levels.quantity', '>', 0)
            ->groupBy('warehouses.code', 'products.sku', 'products.name')
            ->orderBy('warehouses.code')
            ->orderBy('products.sku')
            ->get([
                'warehouses.code as warehouse_code',
                'products.sku',
                'products.name',
                DB::raw('sum(stock_levels.quantity) as quantity'),
            ]);

        return view('legacy.stock.hazmat', ['rows' => $rows]);
    }

    public function export(Request $request): StreamedResponse
    {
        $warehouseId = $request->integer('warehouse');

        $rows = DB::table('stock_levels')
            ->join('products', 'products.id', '=', 'stock_levels.product_id')
            ->join('bins', 'bins.id', '=', 'stock_levels.bin_id')
            ->where('bins.warehouse_id', $warehouseId)
            ->select(['stock_levels.id', 'products.sku', 'products.name', 'bins.code as bin', 'stock_levels.quantity'])
            ->lazyById(1000, 'stock_levels.id', 'id');

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                throw new RuntimeException('Could not open the output stream for the CSV export.');
            }
            fputcsv($out, ['sku', 'name', 'bin', 'quantity'], escape: '');
            foreach ($rows as $row) {
                fputcsv($out, [$row->sku, $row->name, $row->bin, $row->quantity], escape: '');
            }
            fclose($out);
        }, 'stock-'.$warehouseId.'.csv', ['Content-Type' => 'text/csv']);
    }
}
