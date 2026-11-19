<?php

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Products')] class extends Component {
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public ?int $category = null;

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'category'], true)) {
            $this->resetPage();
        }
    }

    /**
     * @return LengthAwarePaginator<int, Product>
     */
    #[Computed]
    public function products(): LengthAwarePaginator
    {
        $search = trim($this->search);

        return Product::query()
            ->with(['category', 'baseUnit'])
            ->withSum('stockLevels as on_hand', 'quantity')
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('sku', 'ilike', "%{$search}%")
                ->orWhere('name', 'ilike', "%{$search}%")))
            ->when($this->category, fn ($query, int $category) => $query->where('category_id', $category))
            ->orderBy('sku')
            ->paginate(25);
    }

    /**
     * @return Collection<int, Category>
     */
    #[Computed]
    public function categories(): Collection
    {
        return Category::query()->orderBy('name')->get(['id', 'name']);
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <flux:heading size="xl">{{ __('Products') }}</flux:heading>

        <div class="flex flex-wrap gap-2">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('SKU or name')" class="w-64" />
            <flux:select wire:model.live="category" :placeholder="__('All categories')" class="w-48">
                <flux:select.option value="">{{ __('All categories') }}</flux:select.option>
                @foreach ($this->categories as $option)
                    <flux:select.option :value="$option->id">{{ $option->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <flux:table :paginate="$this->products">
        <flux:table.columns>
            <flux:table.column>{{ __('SKU') }}</flux:table.column>
            <flux:table.column>{{ __('Name') }}</flux:table.column>
            <flux:table.column>{{ __('Category') }}</flux:table.column>
            <flux:table.column align="end">{{ __('On hand') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->products as $product)
                <flux:table.row :key="$product->id">
                    <flux:table.cell class="font-mono">
                        <flux:link :href="route('products.show', $product)" wire:navigate>{{ $product->sku }}</flux:link>
                    </flux:table.cell>
                    <flux:table.cell>
                        {{ $product->name }}
                        @unless ($product->is_active)
                            <flux:badge size="sm" color="zinc" class="ms-2">{{ __('Inactive') }}</flux:badge>
                        @endunless
                    </flux:table.cell>
                    <flux:table.cell>{{ $product->category?->name }}</flux:table.cell>
                    <flux:table.cell align="end" class="tabular-nums">
                        {{ number_format((int) $product->on_hand) }} {{ $product->baseUnit->code }}
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="4" class="text-center text-zinc-500">{{ __('No products found.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
