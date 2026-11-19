<?php

use App\Models\Product;
use App\Models\StockLevel;
use App\Models\StockMovement;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public Product $product;

    public function mount(Product $product): void
    {
        $this->product = $product->load(['category', 'baseUnit', 'unitConversions.unit']);
    }

    /**
     * @return Collection<int, StockLevel>
     */
    #[Computed]
    public function stock(): Collection
    {
        return $this->product->stockLevels()
            ->with('bin.warehouse')
            ->where('quantity', '!=', 0)
            ->get()
            ->sortBy(fn (StockLevel $level) => $level->bin->warehouse->code.' '.$level->bin->code)
            ->values();
    }

    /**
     * @return Collection<int, StockMovement>
     */
    #[Computed]
    public function movements(): Collection
    {
        return $this->product->stockMovements()
            ->with(['bin.warehouse', 'user'])
            ->latest('occurred_at')
            ->latest('id')
            ->limit(20)
            ->get();
    }

    public function render(): mixed
    {
        return $this->view()->title($this->product->sku);
    }
}; ?>

<div class="flex flex-col gap-8">
    <div>
        <flux:breadcrumbs class="mb-2">
            <flux:breadcrumbs.item :href="route('products.index')" wire:navigate>{{ __('Products') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ $product->sku }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
        <flux:heading size="xl">{{ $product->name }}</flux:heading>
        <flux:text>{{ $product->description }}</flux:text>
    </div>

    <div class="grid gap-6 md:grid-cols-2">
        <flux:card class="space-y-2">
            <flux:heading>{{ __('Details') }}</flux:heading>
            <dl class="grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
                <dt class="text-zinc-500">{{ __('SKU') }}</dt><dd class="font-mono">{{ $product->sku }}</dd>
                <dt class="text-zinc-500">{{ __('Category') }}</dt><dd>{{ $product->category?->name ?? '—' }}</dd>
                <dt class="text-zinc-500">{{ __('Base unit') }}</dt><dd>{{ $product->baseUnit->name }} ({{ $product->baseUnit->code }})</dd>
                @foreach ($product->unitConversions as $conversion)
                    <dt class="text-zinc-500">1 {{ $conversion->unit->code }}</dt><dd>{{ $conversion->base_units_per_unit }} {{ $product->baseUnit->code }}</dd>
                @endforeach
                @foreach ($product->attributes ?? [] as $key => $value)
                    <dt class="text-zinc-500">{{ str($key)->headline() }}</dt><dd>{{ is_bool($value) ? ($value ? __('Yes') : __('No')) : $value }}</dd>
                @endforeach
            </dl>
        </flux:card>

        <flux:card class="space-y-2">
            <flux:heading>{{ __('Stock by bin') }}</flux:heading>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Warehouse') }}</flux:table.column>
                    <flux:table.column>{{ __('Bin') }}</flux:table.column>
                    <flux:table.column align="end">{{ __('Quantity') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($this->stock as $level)
                        <flux:table.row :key="$level->id">
                            <flux:table.cell class="font-mono">{{ $level->bin->warehouse->code }}</flux:table.cell>
                            <flux:table.cell class="font-mono">{{ $level->bin->code }}</flux:table.cell>
                            <flux:table.cell align="end" class="tabular-nums">{{ number_format($level->quantity) }}</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="3" class="text-center text-zinc-500">{{ __('Not in stock.') }}</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>
    </div>

    <div class="space-y-2">
        <flux:heading>{{ __('Recent movements') }}</flux:heading>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('When') }}</flux:table.column>
                <flux:table.column>{{ __('Where') }}</flux:table.column>
                <flux:table.column>{{ __('Reason') }}</flux:table.column>
                <flux:table.column>{{ __('By') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Change') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($this->movements as $movement)
                    <flux:table.row :key="$movement->id">
                        <flux:table.cell>{{ $movement->occurred_at->toDateTimeString() }}</flux:table.cell>
                        <flux:table.cell class="font-mono">{{ $movement->bin->warehouse->code }} / {{ $movement->bin->code }}</flux:table.cell>
                        <flux:table.cell>{{ str($movement->reason->value)->headline() }}</flux:table.cell>
                        <flux:table.cell>{{ $movement->user?->name ?? __('System') }}</flux:table.cell>
                        <flux:table.cell align="end" class="tabular-nums">{{ sprintf('%+d', $movement->quantity_delta) }}</flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="text-center text-zinc-500">{{ __('No movements yet.') }}</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>
</div>
