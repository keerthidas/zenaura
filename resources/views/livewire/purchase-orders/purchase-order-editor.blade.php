<div>
    @php
        $totals = $this->totals;
        $quotedTotals = $this->quotedTotals;
        $lpoNet = $totals['subtotal'] - $totals['discount_total'];
        $quotedNet = $quotedTotals['subtotal'] - $quotedTotals['discount_total'];
        $margin = round($quotedNet - $lpoNet, 2);
        $selectedCount = collect($lines)->where('include', true)->count();
        $fmtQty = fn ($n) => rtrim(rtrim(number_format((float) $n, 3, '.', ''), '0'), '.');
    @endphp

    <div wire:loading.flex wire:target="save" class="fixed inset-0 z-50 items-center justify-center bg-ink/40">
        <div class="flex items-center gap-3 rounded-2xl bg-white px-5 py-4 text-sm shadow-lg">
            <x-spinner class="h-5 w-5 text-zenora" />
            Issuing LPO…
        </div>
    </div>

    <div class="quote-summary-bar mb-5 overflow-hidden rounded-2xl bg-zenora-dark text-white shadow-lg">
        <div class="flex flex-wrap items-stretch">
            <div class="min-w-[12rem] flex-1 px-5 py-4">
                <p class="text-[11px] uppercase tracking-[0.2em] text-teal-200/80">Local purchase order</p>
                <p class="break-all font-display text-2xl">{{ $this->numberPreview }}</p>
                <p class="text-[11px] text-teal-100/70">Number is confirmed when the LPO is issued</p>
            </div>
            <div class="grid flex-[2] grid-cols-2 sm:grid-cols-4">
                <div class="border-l border-white/10 px-4 py-4 text-right">
                    <p class="text-[10px] uppercase tracking-wide text-teal-200/70">Gross</p>
                    <p class="mt-1 text-sm font-semibold">{{ number_format($totals['subtotal'], 2) }}</p>
                </div>
                <div class="border-l border-white/10 px-4 py-4 text-right">
                    <p class="text-[10px] uppercase tracking-wide text-teal-200/70">Discount</p>
                    <p class="mt-1 text-sm font-semibold">{{ number_format($totals['discount_total'], 2) }}</p>
                </div>
                <div class="border-l border-white/10 px-4 py-4 text-right">
                    <p class="text-[10px] uppercase tracking-wide text-teal-200/70">Ex VAT · VAT</p>
                    <p class="mt-1 text-sm font-semibold">{{ number_format($lpoNet, 2) }} · {{ number_format($totals['vat_total'], 2) }}</p>
                </div>
                <div class="border-l border-white/10 bg-black/20 px-4 py-4 text-right">
                    <p class="text-[10px] uppercase tracking-wide text-teal-200/70">Total LPO value</p>
                    <p class="mt-1 font-display text-xl">{{ aed($totals['grand_total']) }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <a href="{{ route('purchase-orders.index', ['quotation' => $quotation->id]) }}" class="text-sm text-zenora hover:underline" wire:navigate>← LPOs for {{ $quotation->number }}</a>
            <h1 class="mt-2 font-display text-3xl">{{ $replacing ? 'Re-issue '.$replacing->number : 'New LPO' }}</h1>
            <p class="text-sm text-stone-500">From quotation <span class="font-medium text-ink">{{ $quotation->number }}</span> · {{ $quotation->client?->name }} · {{ aed($quotation->grand_total) }}</p>
        </div>
    </div>

    @if ($replacing)
        <div class="mb-5 rounded-xl border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-copper">
            Issuing this LPO will cancel <strong>{{ $replacing->number }}</strong> ({{ $replacing->supplier?->name }} · {{ aed($replacing->grand_total) }}). Nothing changes until you click Issue LPO.
        </div>
    @endif

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="card space-y-4 p-5 lg:col-span-2">
            <div>
                <div class="flex items-end justify-between gap-2">
                    <label class="label">Supplier</label>
                    <button type="button" wire:click="$toggle('showNewSupplier')" class="mb-1.5 text-xs text-zenora hover:underline">
                        {{ $showNewSupplier ? 'Close' : '+ New supplier' }}
                    </button>
                </div>
                <select wire:model="supplier_id" class="field">
                    <option value="">Select supplier</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                    @endforeach
                </select>
                @error('supplier_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror

                @if ($showNewSupplier)
                    <div class="mt-3 space-y-3 rounded-xl border border-stone-200 bg-sand/40 p-4">
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
                        <button type="button" wire:click="saveNewSupplier" wire:loading.attr="disabled" wire:target="saveNewSupplier" class="btn btn-primary py-1.5 text-xs">
                            <x-spinner class="h-3 w-3" wire:loading wire:target="saveNewSupplier" />
                            Save supplier
                        </button>
                    </div>
                @endif
            </div>

            <div class="grid gap-3 sm:grid-cols-3">
                <div>
                    <label class="label">LPO date</label>
                    <input type="date" wire:model.live="lpo_date" class="field">
                    @error('lpo_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Supplier quote no.</label>
                    <input wire:model="supplier_quote_number" class="field" placeholder="Defaults to {{ $quotation->number }}">
                    @error('supplier_quote_number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Supplier quote date</label>
                    <input type="date" wire:model="supplier_quote_date" class="field">
                    @error('supplier_quote_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="label">Payment terms</label>
                    <input wire:model="payment_terms" class="field">
                    @error('payment_terms') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Delivery terms</label>
                    <input wire:model="delivery_terms" class="field">
                    @error('delivery_terms') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Delivery availability</label>
                    <input wire:model="delivery_availability" class="field">
                    @error('delivery_availability') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Customer ref.</label>
                    <input wire:model="customer_reference" class="field">
                    @error('customer_reference') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="space-y-5">
            <div class="card p-5">
                <p class="label">Against quotation</p>
                <div class="space-y-1.5 text-sm">
                    <div class="flex justify-between gap-3"><span class="text-stone-500">Quoted value (ex VAT)</span><span>{{ number_format($quotedNet, 2) }}</span></div>
                    <div class="flex justify-between gap-3"><span class="text-stone-500">LPO value (ex VAT)</span><span>{{ number_format($lpoNet, 2) }}</span></div>
                    <div class="flex justify-between gap-3 border-t border-stone-100 pt-1.5 font-semibold">
                        <span>Margin</span>
                        <span class="{{ $margin < 0 ? 'text-red-600' : 'text-zenora-dark' }}">{{ number_format($margin, 2) }}</span>
                    </div>
                </div>
                <p class="mt-2 text-xs text-stone-500">For the {{ $selectedCount }} selected line{{ $selectedCount === 1 ? '' : 's' }}, using quotation prices and discounts.</p>
            </div>

            <div class="card p-5">
                <p class="label">Other active LPOs on this quotation</p>
                @forelse ($otherOrders as $other)
                    <div class="flex items-center justify-between gap-2 border-t border-stone-100 py-1.5 text-sm first:border-t-0">
                        <div class="min-w-0">
                            <p class="truncate font-medium">{{ $other->number }}</p>
                            <p class="truncate text-xs text-stone-500">{{ $other->supplier?->name }}</p>
                        </div>
                        <span class="shrink-0">{{ number_format((float) $other->grand_total, 2) }}</span>
                    </div>
                @empty
                    <p class="text-sm text-stone-500">None yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="card mt-5 overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-stone-100 px-4 py-3">
            <div>
                <h2 class="font-medium">Line items</h2>
                <p class="text-xs text-stone-500">Tick the lines for this supplier. Unit price, qty and discount can differ from the quotation; totals recalculate as you type.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 text-xs">
                <button type="button" wire:click="toggleAll(true)" class="btn btn-ghost px-3 py-1.5 text-xs">Select all</button>
                <button type="button" wire:click="toggleAll(false)" class="btn btn-ghost px-3 py-1.5 text-xs">Select none</button>
                <div class="flex items-center gap-1">
                    <input wire:model="bulk_discount" class="field-compact w-20 text-right" placeholder="Disc %">
                    <button type="button" wire:click="applyDiscountToSelected" class="btn btn-ghost px-3 py-1.5 text-xs">Apply to selected</button>
                </div>
            </div>
        </div>
        @error('bulk_discount') <p class="px-4 pt-2 text-xs text-red-600">{{ $message }}</p> @enderror
        @error('lines') <p class="px-4 pt-2 text-sm text-red-600">{{ $message }}</p> @enderror

        <div class="overflow-x-auto">
            <table class="quote-table">
                <thead>
                    <tr>
                        <th class="w-10"></th>
                        <th class="w-36">Article</th>
                        <th>Description</th>
                        <th class="w-16">Unit</th>
                        <th class="w-24 text-right">Quoted qty</th>
                        <th class="w-20 text-right">Qty</th>
                        <th class="w-24 text-right">Quoted price</th>
                        <th class="w-28 text-right">LPO unit price</th>
                        <th class="w-16 text-right">Disc %</th>
                        <th class="w-24 text-right">Net SP</th>
                        <th class="w-28 text-right">Value (Ex VAT)</th>
                        <th class="w-24 text-right">Inc. VAT</th>
                        <th class="w-8"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($lines as $i => $line)
                        @php
                            $included = ! empty($line['include']);
                            $priceChanged = $line['from_quote'] && ! \App\Services\QuotationCalculator::pricesMatch(
                                \App\Services\QuotationCalculator::number($line['unit_price']),
                                (float) $line['quoted_price'],
                            );
                            $remaining = round((float) $line['quoted_qty'] - (float) $line['ordered_elsewhere'], 3);
                            $overOrdered = $line['from_quote'] && $included
                                && \App\Services\QuotationCalculator::number($line['qty']) - $remaining > 0.0005;
                        @endphp
                        <tr wire:key="lpo-line-{{ $line['key'] }}" class="{{ $included ? '' : 'opacity-50' }}">
                            <td class="border-b border-stone-100 px-3 py-2 text-center align-top">
                                <input type="checkbox" wire:model.live="lines.{{ $i }}.include" class="mt-1.5 h-4 w-4 accent-zenora-dark">
                            </td>
                            <td class="border-b border-stone-100 px-1 py-1 align-top">
                                @if ($line['from_quote'])
                                    <p class="px-1.5 py-1.5 font-semibold uppercase">{{ $line['code'] }}</p>
                                @else
                                    <input wire:model.blur="lines.{{ $i }}.code" class="sheet-input font-semibold uppercase" placeholder="Code">
                                @endif
                            </td>
                            <td class="border-b border-stone-100 px-1 py-1 align-top">
                                @if ($line['heading'] !== '')
                                    <p class="px-1.5 pt-1 text-[10px] uppercase tracking-wide text-stone-400">{{ $line['heading'] }}</p>
                                @endif
                                <textarea wire:model.blur="lines.{{ $i }}.description" rows="1" class="sheet-input sheet-description" placeholder="Description"></textarea>
                            </td>
                            <td class="border-b border-stone-100 px-1 py-1 align-top">
                                <input wire:model.blur="lines.{{ $i }}.unit" class="sheet-input uppercase">
                            </td>
                            <td class="border-b border-stone-100 px-2 py-2 text-right align-top text-stone-600">
                                @if ($line['from_quote'])
                                    {{ $fmtQty($line['quoted_qty']) }}
                                    @if ((float) $line['ordered_elsewhere'] > 0)
                                        <p class="text-[10px] text-copper">{{ $fmtQty($line['ordered_elsewhere']) }} on other LPOs</p>
                                    @endif
                                @else
                                    <span class="text-stone-400">Extra</span>
                                @endif
                            </td>
                            <td class="border-b border-stone-100 px-1 py-1 align-top">
                                <input type="text" inputmode="decimal" wire:model.live.debounce.250ms="lines.{{ $i }}.qty" class="sheet-input text-right" onfocus="this.select()">
                                @if ($overOrdered)
                                    <p class="px-1.5 text-right text-[10px] text-copper">Over quoted qty</p>
                                @endif
                            </td>
                            <td class="border-b border-stone-100 px-2 py-2 text-right align-top text-stone-500">
                                {{ $line['from_quote'] ? number_format((float) $line['quoted_price'], 2) : '—' }}
                            </td>
                            <td class="border-b border-stone-100 px-1 py-1 align-top {{ $priceChanged ? 'bg-amber-50' : '' }}">
                                <input type="text" inputmode="decimal" wire:model.live.debounce.250ms="lines.{{ $i }}.unit_price" class="sheet-input text-right font-semibold" placeholder="0.00" onfocus="this.select()">
                                @if ($priceChanged)
                                    <button type="button" wire:click="resetPrice({{ $i }})" class="block w-full px-1.5 text-right text-[10px] text-zenora hover:underline">Reset to quoted</button>
                                @endif
                            </td>
                            <td class="border-b border-stone-100 px-1 py-1 align-top">
                                <input type="text" inputmode="decimal" wire:model.live.debounce.250ms="lines.{{ $i }}.discount_percent" class="sheet-input text-right" placeholder="0" onfocus="this.select()">
                            </td>
                            <td class="border-b border-stone-100 px-2 py-2 text-right align-top text-stone-600">{{ number_format((float) $line['unit_net'], 2) }}</td>
                            <td class="border-b border-stone-100 px-2 py-2 text-right align-top font-medium">{{ number_format((float) $line['net_amount'], 2) }}</td>
                            <td class="border-b border-stone-100 px-2 py-2 text-right align-top text-stone-600">{{ number_format((float) $line['line_total'], 2) }}</td>
                            <td class="border-b border-stone-100 px-1 py-2 text-center align-top">
                                @unless ($line['from_quote'])
                                    <button type="button" wire:click="removeLine({{ $i }})" class="text-red-600 hover:text-red-800" title="Remove line">✕</button>
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
            <button type="button" wire:click="addLine" class="text-sm text-zenora hover:underline">+ Add extra line</button>
            <p class="text-sm text-stone-500">{{ $selectedCount }} of {{ count($lines) }} line{{ count($lines) === 1 ? '' : 's' }} selected</p>
        </div>
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-3">
        <div class="card p-5">
            <label class="label">Buyer details</label>
            <textarea wire:model="buyer_details" rows="6" class="field"></textarea>
            @error('buyer_details') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="card p-5">
            <label class="label">Purchase order terms</label>
            <textarea wire:model="terms" rows="6" class="field" placeholder="Leave blank to generate from the quotation reference, delivery, payment and availability above. One term per line."></textarea>
            @error('terms') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="card p-5">
            <label class="label">Internal notes</label>
            <textarea wire:model="notes" rows="6" class="field" placeholder="Not printed on the LPO"></textarea>
            @error('notes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="mt-6 flex flex-wrap gap-2">
        <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save" class="btn btn-copper">
            {{ $replacing ? 'Issue LPO & cancel '.$replacing->number : 'Issue LPO' }}
        </button>
        <a href="{{ route('purchase-orders.index', ['quotation' => $quotation->id]) }}" class="btn btn-ghost" wire:navigate>Cancel</a>
    </div>
</div>
