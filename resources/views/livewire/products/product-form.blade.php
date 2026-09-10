<div class="mx-auto max-w-3xl">
    <div class="mb-6">
        <a href="{{ route('products.index') }}" class="text-sm text-zenora hover:underline" wire:navigate>← Products</a>
        <h1 class="mt-2 font-display text-3xl">{{ $product?->exists ? 'Edit product' : 'New product' }}</h1>
        <p class="text-sm text-stone-500">Code is entered by you — it is not auto-generated.</p>
    </div>

    <form wire:submit="save" class="card space-y-5 p-6">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label">Code</label>
                <input wire:model="code" class="field uppercase" placeholder="TAP-001">
                @error('code') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">Unit</label>
                <input wire:model="unit" class="field" placeholder="pcs / box / set / m">
                @error('unit') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
        <div>
            <label class="label">Description</label>
            <textarea wire:model="description" rows="3" class="field" placeholder="Chrome basin mixer, single lever"></textarea>
            @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label">Actual price (AED, internal)</label>
                <input type="number" step="0.01" min="0" wire:model="actual_price" class="field">
                @error('actual_price') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">Selling price (AED)</label>
                <input type="number" step="0.01" min="0" wire:model="selling_price" class="field">
                @error('selling_price') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" wire:model="is_active" class="rounded border-stone-300 text-zenora focus:ring-zenora">
            Active in quotation autocomplete
        </label>
        <div>
            <label class="label">Images</label>
            <input type="file" wire:model="photos" multiple accept="image/*" class="block text-sm">
            <div wire:loading wire:target="photos" class="mt-1 text-xs text-stone-500">Uploading…</div>
            @error('photos.*') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            <div class="mt-3 flex flex-wrap gap-3">
                @foreach ($photos as $photo)
                    <img src="{{ $photo->temporaryUrl() }}" class="h-20 w-20 rounded-lg object-cover" alt="">
                @endforeach
                @foreach ($product?->images ?? [] as $image)
                    <div class="relative">
                        <img src="{{ $image->url() }}" class="h-20 w-20 rounded-lg object-cover" alt="Product photo">
                        <button type="button" wire:click="removeImage({{ $image->id }})" class="absolute -right-1 -top-1 rounded-full bg-red-600 px-1.5 text-xs text-white">×</button>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="flex gap-3">
            <button class="btn btn-primary">Save product</button>
            <a href="{{ route('products.index') }}" class="btn btn-ghost" wire:navigate>Cancel</a>
        </div>
    </form>
</div>
