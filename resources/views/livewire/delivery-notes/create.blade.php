<div class="mx-auto max-w-6xl">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <a href="{{ route('delivery-notes.index') }}" class="text-sm text-zenora hover:underline" wire:navigate>← Delivery notes</a>
            <p class="mt-3 text-xs uppercase tracking-[0.2em] text-stone-500">Logistics</p>
            <h1 class="font-display text-3xl">New delivery note</h1>
        </div>
    </div>

    <div class="grid gap-5 lg:grid-cols-2">
        <section class="card space-y-4 p-5">
            <h2 class="font-medium">Quotation</h2>
            <div>
                <label class="label">Select quotation</label>
                <select wire:model.live="quotationId" class="field">
                    <option value="">Choose a quotation</option>
                    @foreach ($quotations as $quotation)
                        <option value="{{ $quotation->id }}">{{ $quotation->number }} · {{ $quotation->client?->name }} · {{ $quotation->quote_date?->format('d M Y') }}</option>
                    @endforeach
                </select>
                @error('quotationId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            @if ($selectedQuotation = $quotations->firstWhere('id', (int) $quotationId))
                <div class="border-t border-stone-100 pt-3 text-sm">
                    <p class="font-medium">{{ $selectedQuotation->client?->name }}</p>
                    <p class="mt-1 text-stone-500">{{ $selectedQuotation->number }} · {{ $selectedQuotation->quote_date?->format('d M Y') }} · {{ ucfirst($selectedQuotation->status) }}</p>
                </div>
            @endif
        </section>

        <section class="card space-y-4 p-5">
            <h2 class="font-medium">Supplier and delivery</h2>
            <div>
                <div class="flex items-end justify-between gap-2">
                    <label class="label">Supplier</label>
                    <button type="button" wire:click="$toggle('showNewSupplier')" class="mb-1.5 text-xs text-zenora hover:underline">
                        {{ $showNewSupplier ? 'Close' : '+ Add supplier' }}
                    </button>
                </div>
                <select wire:model="supplier_id" class="field">
                    <option value="">Select supplier</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                    @endforeach
                </select>
                @error('supplier_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            @if ($showNewSupplier)
                <div class="space-y-3 border-t border-stone-100 pt-4">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="label">Company name</label>
                            <input wire:model="ns_name" class="field">
                            @error('ns_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">Contact person</label>
                            <input wire:model="ns_contact_person" class="field">
                        </div>
                    </div>
                    <div>
                        <label class="label">Address</label>
                        <textarea wire:model="ns_address" rows="2" class="field"></textarea>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-3">
                        <div>
                            <label class="label">Phone</label>
                            <input wire:model="ns_phone" class="field">
                        </div>
                        <div>
                            <label class="label">Email</label>
                            <input wire:model="ns_email" class="field">
                            @error('ns_email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">VAT no.</label>
                            <input wire:model="ns_trn" class="field">
                        </div>
                    </div>
                    <button type="button" wire:click="saveNewSupplier" class="btn btn-primary py-1.5 text-xs">Save and select supplier</button>
                </div>
            @endif

            <div>
                <label class="label">Delivery date</label>
                <input type="date" wire:model="deliveryDate" class="field">
                @error('deliveryDate') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </section>
    </div>

    @if ($quotationId !== '')
        <section class="card mt-5 overflow-hidden">
            <div class="flex flex-wrap items-end justify-between gap-2 border-b border-stone-100 px-5 py-4">
                <div>
                    <h2 class="font-medium">Materials delivered</h2>
                    <p class="mt-1 text-xs text-stone-500">Select the lines on this delivery and enter the delivered quantity.</p>
                </div>
                @php
                    $excessCount = collect($lines)->filter(fn ($line) => (bool) ($line['include'] ?? false)
                        && (float) ($line['delivered_qty'] ?? 0) > (float) ($line['remaining_qty'] ?? 0))->count();
                @endphp
                @if ($excessCount)
                    <span class="text-xs font-medium text-amber-800">{{ $excessCount }} line(s) exceed the remaining quoted quantity. You can still save this note.</span>
                @endif
            </div>
            @error('lines') <p class="px-5 pt-3 text-sm text-red-600">{{ $message }}</p> @enderror
            <div class="overflow-x-auto">
                <table class="min-w-[900px] w-full text-sm">
                    <thead class="bg-sand/60 text-left text-xs uppercase tracking-wide text-stone-500">
                        <tr>
                            <th class="px-3 py-3">Include</th>
                            <th class="px-3 py-3">Material</th>
                            <th class="px-3 py-3">Unit</th>
                            <th class="px-3 py-3 text-right">Quoted</th>
                            <th class="px-3 py-3 text-right">Previously delivered</th>
                            <th class="px-3 py-3 text-right">Remaining</th>
                            <th class="px-3 py-3">Delivered now</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($lines as $index => $line)
                            @php
                                $excess = max(0, (float) ($line['delivered_before'] ?? 0) + (float) ($line['delivered_qty'] ?? 0) - (float) ($line['quoted_qty'] ?? 0));
                            @endphp
                            <tr class="border-t border-stone-100 align-top {{ $line['include'] ? 'bg-white' : 'bg-stone-50/60' }}">
                                <td class="px-3 py-3"><input type="checkbox" wire:model.live="lines.{{ $index }}.include" aria-label="Include {{ $line['description'] }}"></td>
                                <td class="max-w-md px-3 py-3">
                                    <p class="font-medium">{{ $line['description'] ?: 'Untitled material' }}</p>
                                    @if ($line['code']) <p class="mt-0.5 text-xs text-stone-500">{{ $line['code'] }}</p> @endif
                                </td>
                                <td class="px-3 py-3">{{ $line['unit'] }}</td>
                                <td class="px-3 py-3 text-right tabular-nums">{{ \App\Livewire\DeliveryNotes\Create::formatQuantity((float) $line['quoted_qty']) }}</td>
                                <td class="px-3 py-3 text-right tabular-nums">{{ \App\Livewire\DeliveryNotes\Create::formatQuantity((float) $line['delivered_before']) }}</td>
                                <td class="px-3 py-3 text-right tabular-nums">{{ \App\Livewire\DeliveryNotes\Create::formatQuantity((float) $line['remaining_qty']) }}</td>
                                <td class="w-40 px-3 py-2">
                                    <input type="number" min="0" step="0.001" wire:model.live.debounce.200ms="lines.{{ $index }}.delivered_qty" @disabled(! $line['include']) class="field-compact text-right tabular-nums">
                                    @if ($excess > 0 && $line['include'])
                                        <p class="mt-1 text-right text-xs text-amber-800">Over by {{ \App\Livewire\DeliveryNotes\Create::formatQuantity($excess) }}</p>
                                    @endif
                                    @error("lines.{$index}.delivered_qty") <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-10 text-center text-sm text-stone-500">This quotation has no material lines.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card mt-5 space-y-4 p-5">
            <div>
                <label class="label">Delivery note remarks (optional)</label>
                <textarea wire:model="notes" rows="3" class="field" placeholder="Delivery condition, location, or other remarks"></textarea>
                @error('notes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="flex justify-end">
                <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save" class="btn btn-primary">
                    <x-spinner class="h-4 w-4" wire:loading wire:target="save" />
                    Create delivery note
                </button>
            </div>
        </section>
    @endif
</div>