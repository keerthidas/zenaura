<div class="mx-auto max-w-4xl">
    <div class="mb-6">
        <a href="{{ $lead?->exists ? route('leads.show', $lead) : route('leads.index') }}" class="text-sm text-zenora hover:underline" wire:navigate>← Sales CRM</a>
        <h1 class="mt-2 font-display text-3xl">{{ $lead?->exists ? 'Edit lead' : 'New lead' }}</h1>
        <p class="text-sm text-stone-500">Capture the prospect and keep the sales conversation moving.</p>
    </div>

    <form wire:submit="save" class="card space-y-5 p-6">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label">Contact name *</label>
                <input wire:model="name" class="field" autocomplete="name">
                @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">Company</label>
                <input wire:model="company" class="field" autocomplete="organization">
            </div>
            <div>
                <label class="label">Phone</label>
                <input wire:model="phone" class="field" autocomplete="tel">
            </div>
            <div>
                <label class="label">Email</label>
                <input type="email" wire:model="email" class="field" autocomplete="email">
                @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">Lead source</label>
                <input wire:model="source" class="field" placeholder="Referral, website, walk-in">
            </div>
            <div>
                <label class="label">Emirate</label>
                <select wire:model="emirate" class="field">
                    <option value="">Select emirate</option>
                    @foreach (zenora_emirates() as $emirate)
                        <option value="{{ $emirate }}">{{ $emirate }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Estimated opportunity value (AED)</label>
                <input type="number" min="0" step="0.01" wire:model="estimated_value" class="field">
                @error('estimated_value') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">Expected close date</label>
                <input type="date" wire:model="expected_close_date" class="field">
            </div>
        </div>
        <div>
            <label class="label">Address</label>
            <input wire:model="address" class="field">
        </div>
        <div>
            <label class="label">Products / requirements of interest</label>
            <textarea wire:model="interested_in" rows="2" class="field" placeholder="Products, brands, project requirements"></textarea>
        </div>
        <div>
            <label class="label">Pipeline stage</label>
            <select wire:model.live="status" class="field">
                @foreach (['new', 'contacted', 'qualified', 'quotation', 'negotiation', 'lost'] as $status)
                    <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
        @if ($status === 'lost')
            <div>
                <label class="label">Reason lost *</label>
                <input wire:model="lost_reason" class="field" placeholder="Price, timing, competitor, no response">
                @error('lost_reason') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        @endif
        <div>
            <label class="label">Lead notes</label>
            <textarea wire:model="notes" rows="3" class="field"></textarea>
        </div>
        <div class="flex gap-3">
            <button class="btn btn-primary">{{ $lead?->exists ? 'Save changes' : 'Create lead' }}</button>
            <a href="{{ $lead?->exists ? route('leads.show', $lead) : route('leads.index') }}" class="btn btn-ghost" wire:navigate>Cancel</a>
        </div>
    </form>
</div>
