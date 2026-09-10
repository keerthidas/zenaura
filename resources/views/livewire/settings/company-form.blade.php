<div class="mx-auto max-w-3xl">
    <h1 class="mb-6 font-display text-3xl">Company settings</h1>
    <form wire:submit="save" class="card space-y-4 p-6">
        <div>
            <label class="label">Company name</label>
            <input wire:model="name" class="field">
            @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label">TRN</label>
                <input wire:model="trn" class="field">
            </div>
            <div>
                <label class="label">Emirate</label>
                <select wire:model="emirate" class="field">
                    <option value="">Select</option>
                    @foreach (zenora_emirates() as $item)
                        <option value="{{ $item }}">{{ $item }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div>
            <label class="label">Address</label>
            <input wire:model="address" class="field">
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label">Phone</label>
                <input wire:model="phone" class="field">
            </div>
            <div>
                <label class="label">Email</label>
                <input type="email" wire:model="email" class="field">
                @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
        <div>
            <label class="label">Website</label>
            <input wire:model="website" class="field" placeholder="www.example.com">
        </div>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <div>
                <label class="label">Quote prefix</label>
                <input wire:model="quote_prefix" class="field uppercase">
            </div>
            <div>
                <label class="label">Invoice prefix</label>
                <input wire:model="invoice_prefix" class="field uppercase">
            </div>
            <div>
                <label class="label">Default VAT %</label>
                <input type="number" step="0.01" wire:model="default_vat_percent" class="field">
            </div>
            <div>
                <label class="label">Validity (days)</label>
                <input type="number" wire:model="default_validity_days" class="field">
            </div>
            <div>
                <label class="label">Default unit</label>
                <input wire:model="default_unit" class="field">
            </div>
        </div>
        <div>
            <label class="label">Logo</label>
            <input type="file" wire:model="logo" accept="image/*" class="block text-sm">
            @error('logo') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            @if ($logo)
                <img src="{{ $logo->temporaryUrl() }}" class="mt-3 h-16 object-contain" alt="">
            @elseif ($existingLogo)
                <img src="{{ public_media_url($existingLogo) }}" class="mt-3 h-16 object-contain" alt="Company logo">
            @endif
        </div>
        <div>
            <label class="label">Default quotation terms</label>
            <textarea wire:model="default_terms" rows="5" class="field"></textarea>
        </div>
        <div>
            <label class="label">Bank details (printed on invoices)</label>
            <textarea wire:model="bank_details" rows="3" class="field" placeholder="A/c Name : ... A/c No : ... Bank.Name : ... IBAN : ... BIC Code : ..."></textarea>
        </div>
        <button class="btn btn-primary">Save settings</button>
    </form>
</div>
