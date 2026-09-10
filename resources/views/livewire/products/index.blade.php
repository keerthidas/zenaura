<div>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs uppercase tracking-[0.2em] text-stone-500">Catalogue</p>
            <h1 class="font-display text-3xl">Products</h1>
        </div>
        <a href="{{ route('products.create') }}" class="btn btn-primary" wire:navigate>Add product</a>
    </div>

    <div class="mb-4">
        <input wire:model.live.debounce.300ms="search" class="field max-w-md" placeholder="Search code or description">
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-sand/60 text-left text-xs uppercase tracking-wide text-stone-500">
                    <tr>
                        <th class="px-4 py-3">Image</th>
                        <th class="px-4 py-3">Code</th>
                        <th class="px-4 py-3">Description</th>
                        <th class="px-4 py-3">Unit</th>
                        <th class="px-4 py-3 text-right">Actual</th>
                        <th class="px-4 py-3 text-right">Selling</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr class="border-t border-stone-100 align-top hover:bg-sand/30">
                            <td class="px-4 py-3">
                                @if ($product->images->first())
                                    <img src="{{ $product->images->first()->url() }}" alt="{{ $product->code }}" class="h-12 w-12 rounded-lg object-cover">
                                @else
                                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-sand text-[10px] text-stone-400">No img</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-semibold">{{ $product->code }}</td>
                            <td class="px-4 py-3 max-w-sm">{{ $product->description }}</td>
                            <td class="px-4 py-3">{{ $product->unit }}</td>
                            <td class="px-4 py-3 text-right text-stone-500">{{ aed($product->actual_price) }}</td>
                            <td class="px-4 py-3 text-right">{{ aed($product->selling_price) }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('products.edit', $product) }}" class="text-zenora hover:underline" wire:navigate>Edit</a>
                                <button wire:click="delete({{ $product->id }})" wire:confirm="Delete this product?" class="ml-3 text-red-600 hover:underline">Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-stone-500">No products yet. Add codes from here or while building a quotation.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-stone-100 px-4 py-3">{{ $products->links() }}</div>
    </div>
</div>
