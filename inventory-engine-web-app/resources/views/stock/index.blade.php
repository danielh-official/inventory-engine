<x-layouts::app :title="__('Stock')">
    <div class="flex flex-col gap-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <flux:heading size="xl">{{ __('Stock') }}</flux:heading>
                <flux:text>{{ __('Total on hand') }}: {{ number_format($total) }}</flux:text>
            </div>

            <form method="GET" action="{{ route('stock.index') }}" class="flex flex-wrap items-end gap-2">
                <select name="warehouse" class="rounded-md border border-zinc-300 px-2 py-1.5 dark:border-zinc-600 dark:bg-zinc-800" onchange="this.form.submit()">
                    @foreach ($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" @selected($warehouse->id == $warehouseId)>{{ $warehouse->code }} — {{ $warehouse->name }}</option>
                    @endforeach
                </select>
                <input type="search" name="q" value="{{ $search }}" placeholder="{{ __('SKU, name or bin') }}" class="rounded-md border border-zinc-300 px-2 py-1.5 dark:border-zinc-600 dark:bg-zinc-800">
                <flux:button type="submit" size="sm">{{ __('Search') }}</flux:button>
                <flux:button size="sm" variant="ghost" :href="route('stock.export', ['warehouse' => $warehouseId])">{{ __('Export CSV') }}</flux:button>
            </form>
        </div>

        @if (session('status'))
            <flux:callout variant="success" icon="check-circle">{{ session('status') }}</flux:callout>
        @endif

        <form method="POST" action="{{ route('stock.adjust') }}" class="flex flex-wrap items-end gap-2 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
            @csrf
            <label class="flex flex-col text-sm">{{ __('Product') }}
                <select name="product_id" required class="rounded-md border border-zinc-300 px-2 py-1.5 dark:border-zinc-600 dark:bg-zinc-800">
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" @selected(old('product_id') == $product->id)>{{ $product->sku }} — {{ $product->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="flex flex-col text-sm">{{ __('Bin') }}
                <select name="bin_id" required class="rounded-md border border-zinc-300 px-2 py-1.5 dark:border-zinc-600 dark:bg-zinc-800">
                    @foreach ($bins as $bin)
                        <option value="{{ $bin->id }}" @selected(old('bin_id') == $bin->id)>{{ $bin->code }}</option>
                    @endforeach
                </select>
            </label>
            <label class="flex flex-col text-sm">{{ __('Quantity (+/-)') }}
                <input type="number" name="quantity" value="{{ old('quantity') }}" required class="w-32 rounded-md border border-zinc-300 px-2 py-1.5 dark:border-zinc-600 dark:bg-zinc-800">
            </label>
            <flux:button type="submit" variant="primary" size="sm">{{ __('Adjust') }}</flux:button>
            @if ($errors->any())
                <flux:text class="w-full text-red-600">{{ $errors->first() }}</flux:text>
            @endif
        </form>

        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                    <th class="py-2">{{ __('SKU') }}</th>
                    <th>{{ __('Product') }}</th>
                    <th>{{ __('Bin') }}</th>
                    <th class="text-right">{{ __('Quantity') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr class="border-b border-zinc-100 dark:border-zinc-800">
                        <td class="py-1.5 font-mono">{{ $row->sku }}</td>
                        <td>{{ $row->product_name }}</td>
                        <td class="font-mono">{{ $row->bin_code }}</td>
                        <td class="text-right tabular-nums">{{ number_format($row->quantity) }} {{ $row->unit_code }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-6 text-center text-zinc-500">{{ __('No stock.') }}</td></tr>
                @endforelse
            </tbody>
        </table>

        {{ $rows->links() }}
    </div>
</x-layouts::app>
