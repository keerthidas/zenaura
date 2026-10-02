<div class="mx-auto max-w-3xl">
    <div class="mb-6">
        <a href="{{ route('suppliers.index') }}" class="text-sm text-zenora hover:underline" wire:navigate>← Suppliers</a>
        <h1 class="mt-2 font-display text-3xl">{{ $supplier?->exists ? 'Edit supplier' : 'New supplier' }}</h1>
        <p class="text-sm text-stone-500">Shown in the supplier block of every LPO.</p>
    </div>
    <form wire:submit="save" class="card space-y-4 p-6">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label">Company name</label>
                <input wire:model="name" class="field">
                @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">Contact person</label>
                <input wire:model="contact_person" class="field">
            </div>
        </div>
        <div>
            <label class="label">Address</label>
            <textarea wire:model="address" rows="3" class="field" placeholder="Building&#10;Street&#10;PO Box, City, UAE"></textarea>
            @error('address') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label class="label">Phone</label>
                <input wire:model="phone" class="field">
            </div>
            <div>
                <label class="label">Email</label>
                <input type="email" wire:model="email" class="field">
                @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">VAT no. (TRN)</label>
                <input wire:model="trn" class="field">
            </div>
        </div>
        <div>
            <label class="label">Notes</label>
            <textarea wire:model="notes" rows="3" class="field"></textarea>
        </div>
        <div class="flex gap-3">
            <button class="btn btn-primary">Save supplier</button>
            <a href="{{ route('suppliers.index') }}" class="btn btn-ghost" wire:navigate>Cancel</a>
        </div>
    </form>
</div>
