<x-layouts::app :title="__('Hazmat report')">
    <div class="flex flex-col gap-6">
        <flux:heading size="xl">{{ __('Hazmat on hand') }}</flux:heading>

        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                    <th class="py-2">{{ __('Warehouse') }}</th>
                    <th>{{ __('SKU') }}</th>
                    <th>{{ __('Product') }}</th>
                    <th class="text-right">{{ __('Quantity') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr class="border-b border-zinc-100 dark:border-zinc-800">
                        <td class="py-1.5 font-mono">{{ $row->warehouse_code }}</td>
                        <td class="font-mono">{{ $row->sku }}</td>
                        <td>{{ $row->name }}</td>
                        <td class="text-right tabular-nums">{{ number_format($row->quantity) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-6 text-center text-zinc-500">{{ __('No hazmat stock.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts::app>
