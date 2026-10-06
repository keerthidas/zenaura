<div class="mx-auto max-w-3xl">
    <div class="mb-6">
        <a href="{{ route('clients.index') }}" class="text-sm text-zenora hover:underline" wire:navigate>← Clients</a>
        <h1 class="mt-2 font-display text-3xl">{{ $client?->exists ? 'Edit client' : 'New client' }}</h1>
        <p class="text-sm text-stone-500">Add tax registration details when available.</p>
    </div>
    <form wire:submit="save" class="card space-y-4 p-6">
        <div>
            <label class="label">Company / contact name</label>
            <input wire:model="name" class="field">
            @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label">TRN</label>
                <input wire:model="trn" class="field" placeholder="Tax Registration Number">
                @error('trn') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">Emirate</label>
                <select wire:model="emirate" class="field">
                    <option value="">Select</option>
                    @foreach (zenora_emirates() as $emirate)
                        <option value="{{ $emirate }}">{{ $emirate }}</option>
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
            <label class="label">Notes</label>
            <textarea wire:model="notes" rows="3" class="field"></textarea>
        </div>
        <div class="flex gap-3">
            <button class="btn btn-primary">Save client</button>
            <a href="{{ route('clients.index') }}" class="btn btn-ghost" wire:navigate>Cancel</a>
        </div>
    </form>
</div>
