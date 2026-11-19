<?php

use App\Actions\Stock\RecordAdjustment;
use App\Enums\AdjustmentReason;
use App\Exceptions\InsufficientStock;
use App\Models\Bin;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\Warehouse;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Stock')] class extends Component {
    use WithPagination;

    #[Url]
    public ?int $warehouse = null;

    #[Url]
    public string $search = '';

    public string $sku = '';

    public ?int $binId = null;

    public ?int $quantity = null;

    public string $reason = '';

    public string $note = '';

    public function mount(): void
    {
        $this->warehouse ??= Warehouse::query()->orderBy('code')->value('id');
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['warehouse', 'search'], true)) {
            $this->resetPage();
        }
    }

    /**
     * @return Collection<int, Warehouse>
     */
    #[Computed]
    public function warehouses(): Collection
    {
        return Warehouse::query()->orderBy('code')->get(['id', 'code', 'name']);
    }

    /**
     * @return Collection<int, Bin>
     */
    #[Computed]
    public function bins(): Collection
    {
        return Bin::query()->where('warehouse_id', $this->warehouse)->orderBy('code')->get(['id', 'code']);
    }

    /**
     * @return LengthAwarePaginator<int, StockLevel>
     */
    #[Computed]
    public function levels(): LengthAwarePaginator
    {
        $search = trim($this->search);

        return StockLevel::query()
            ->select('stock_levels.*')
            ->join('products', 'products.id', '=', 'stock_levels.product_id')
            ->join('bins', 'bins.id', '=', 'stock_levels.bin_id')
            ->where('bins.warehouse_id', $this->warehouse)
            ->where('stock_levels.quantity', '!=', 0)
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('products.sku', 'ilike', "%{$search}%")
                ->orWhere('products.name', 'ilike', "%{$search}%")
                ->orWhere('bins.code', 'ilike', "%{$search}%")))
            ->with(['product.baseUnit', 'bin'])
            ->orderBy('products.sku')
            ->orderBy('bins.code')
            ->paginate(50);
    }

    public function adjust(RecordAdjustment $recordAdjustment): void
    {
        $this->validate([
            'sku' => ['required', 'string', Rule::exists('products', 'sku')],
            'binId' => ['required', 'integer', Rule::exists('bins', 'id')->where('warehouse_id', $this->warehouse)],
            'quantity' => ['required', 'integer', 'not_in:0', 'between:-1000000,1000000'],
            'reason' => ['required', Rule::enum(AdjustmentReason::class)],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $recordAdjustment->handle(
                Product::query()->where('sku', $this->sku)->firstOrFail(),
                Bin::query()->findOrFail($this->binId),
                (int) $this->quantity,
                AdjustmentReason::from($this->reason),
                $this->note !== '' ? $this->note : null,
                Auth::user(),
            );
        } catch (InsufficientStock $e) {
            $this->addError('quantity', $e->getMessage());

            return;
        }

        $this->reset('sku', 'binId', 'quantity', 'reason', 'note');
        unset($this->levels);
        Flux::modal('adjust')->close();
        Flux::toast(variant: 'success', text: __('Stock adjusted.'));
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <flux:heading size="xl">{{ __('Stock') }}</flux:heading>

        <div class="flex flex-wrap gap-2">
            <flux:select wire:model.live="warehouse" class="w-56">
                @foreach ($this->warehouses as $option)
                    <flux:select.option :value="$option->id">{{ $option->code }} — {{ $option->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('SKU, name or bin')" class="w-64" />
            <flux:modal.trigger name="adjust">
                <flux:button variant="primary" icon="adjustments-horizontal">{{ __('Adjust') }}</flux:button>
            </flux:modal.trigger>
        </div>
    </div>

    <flux:table :paginate="$this->levels">
        <flux:table.columns>
            <flux:table.column>{{ __('SKU') }}</flux:table.column>
            <flux:table.column>{{ __('Product') }}</flux:table.column>
            <flux:table.column>{{ __('Bin') }}</flux:table.column>
            <flux:table.column align="end">{{ __('Quantity') }}</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($this->levels as $level)
                <flux:table.row :key="$level->id">
                    <flux:table.cell class="font-mono">
                        <flux:link :href="route('products.show', $level->product)" wire:navigate>{{ $level->product->sku }}</flux:link>
                    </flux:table.cell>
                    <flux:table.cell>{{ $level->product->name }}</flux:table.cell>
                    <flux:table.cell class="font-mono">{{ $level->bin->code }}</flux:table.cell>
                    <flux:table.cell align="end" class="tabular-nums">{{ number_format($level->quantity) }} {{ $level->product->baseUnit->code }}</flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="4" class="text-center text-zinc-500">{{ __('No stock.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <flux:modal name="adjust" class="md:w-96">
        <form wire:submit="adjust" class="space-y-4">
            <flux:heading size="lg">{{ __('Adjust stock') }}</flux:heading>
            <flux:input wire:model="sku" :label="__('SKU')" required />
            <flux:select wire:model="binId" :label="__('Bin')" :placeholder="__('Choose a bin')" required>
                @foreach ($this->bins as $bin)
                    <flux:select.option :value="$bin->id">{{ $bin->code }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model="quantity" type="number" :label="__('Quantity (+/-)')" required />
            <flux:select wire:model="reason" :label="__('Reason')" :placeholder="__('Choose a reason')" required>
                @foreach (AdjustmentReason::cases() as $case)
                    <flux:select.option :value="$case->value">{{ $case->label() }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:textarea wire:model="note" :label="__('Note')" rows="2" />
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
