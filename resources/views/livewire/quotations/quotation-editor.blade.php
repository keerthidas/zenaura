<div>
    <div wire:loading.flex wire:target="save" class="fixed inset-0 z-50 items-center justify-center bg-ink/40">
        <div class="flex items-center gap-3 rounded-2xl bg-white px-5 py-4 text-sm shadow-lg">
            <x-spinner class="h-5 w-5 text-zenora" />
            Saving quotation…
        </div>
    </div>
    <div class="quote-summary-bar mb-5 overflow-hidden rounded-2xl bg-zenora-dark text-white shadow-lg">
        <div class="flex flex-wrap items-stretch">
            <div class="min-w-[12rem] flex-1 px-5 py-4">
                <p class="text-[11px] uppercase tracking-[0.2em] text-teal-200/80">Quotation</p>
                <p class="break-all font-display text-2xl">{{ $number ?: 'New' }}</p>
            </div>
            <div class="grid flex-[2] grid-cols-2 sm:grid-cols-4">
                <div class="border-l border-white/10 px-4 py-4 text-right">
                    <p class="text-[10px] uppercase tracking-wide text-teal-200/70">Discount</p>
                    <p class="mt-1 text-sm font-semibold">{{ number_format($this->totals['discount_total'], 2) }}</p>
                </div>
                <div class="border-l border-white/10 px-4 py-4 text-right">
                    <p class="text-[10px] uppercase tracking-wide text-teal-200/70">Ex VAT</p>
                    <p class="mt-1 text-sm font-semibold">{{ number_format($this->totals['subtotal'] - $this->totals['discount_total'], 2) }}</p>
                </div>
                <div class="border-l border-white/10 px-4 py-4 text-right">
                    <p class="text-[10px] uppercase tracking-wide text-teal-200/70">VAT</p>
                    <p class="mt-1 text-sm font-semibold">{{ number_format($this->totals['vat_total'], 2) }}</p>
                </div>
                <div class="border-l border-white/10 bg-black/20 px-4 py-4 text-right">
                    <p class="text-[10px] uppercase tracking-wide text-teal-200/70">Inc. VAT</p>
                    <p class="mt-1 font-display text-xl">{{ aed($this->totals['grand_total']) }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <a href="{{ route('quotations.index') }}" class="text-sm text-zenora hover:underline" wire:navigate>← Quotations</a>
            <h1 class="mt-2 font-display text-3xl">{{ $quotation?->exists ? $quotation->number : ($revisingFromId ? $number : 'New quotation') }}</h1>
            @if ($revisingFromId && ! $quotation?->exists)
                <p class="mt-1 text-sm text-copper">This is a revision. It gets a new quotation number and is only saved when you click Save quotation.</p>
            @elseif ($quotation?->exists && $quotation->isDraft())
                <p class="text-sm text-stone-500">Draft — saving keeps the same quotation number. Headings are optional.</p>
            @else
                <p class="text-sm text-stone-500">Type a product code on a row to pick from the catalogue. Headings are optional.</p>
            @endif
        </div>
        <div class="flex flex-wrap items-end gap-2">
            <div>
                <label class="label">Quote status</label>
                <select wire:model="status" class="field min-w-[10rem] capitalize">
                    @foreach (\App\Models\Quotation::STATUSES as $item)
                        <option value="{{ $item }}">{{ $item }}</option>
                    @endforeach
                </select>
            </div>
            @if ($quotation?->exists)
                <a href="{{ route('quotations.pdf', $quotation) }}" target="_blank" class="btn btn-ghost">Open PDF</a>
                @if ($quotation->isAccepted())
                    <a href="{{ route('quotations.invoice', $quotation) }}" target="_blank" class="btn btn-ghost">Open invoice</a>
                @endif
            @endif
            <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save" class="btn btn-primary">
                <x-spinner class="h-4 w-4" wire:loading wire:target="save" />
                <span wire:loading.remove wire:target="save">Save quotation</span>
                <span wire:loading wire:target="save">Saving…</span>
            </button>
        </div>
    </div>

    @error('client_id') <p class="mb-3 text-sm text-red-600">{{ $message }}</p> @enderror
    @error('number') <p class="mb-3 text-sm text-red-600">{{ $message }}</p> @enderror
    @error('lines') <p class="mb-3 text-sm text-red-600">{{ $message }}</p> @enderror

    <div class="card mb-5 grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-4">
        <div>
            <label class="label">Quotation no.</label>
            <input wire:model="number" class="field font-semibold tracking-wide">
        </div>
        <div>
            <label class="label">Date</label>
            <input type="date" wire:model.live="quote_date" class="field">
        </div>
        <div>
            <label class="label">Validity</label>
            <input type="date" wire:model="valid_until" class="field">
        </div>
        <div>
            <label class="label">Quote status</label>
            <select wire:model="status" class="field capitalize">
                @foreach (\App\Models\Quotation::STATUSES as $item)
                    <option value="{{ $item }}">{{ $item }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-[11px] text-stone-400">Change takes effect when you save.</p>
        </div>
        <div class="relative xl:col-span-2">
            <label class="label">Client</label>
            <div class="flex gap-2">
                <input wire:model.live.debounce.250ms="client_query" class="field" placeholder="Search name or TRN">
                <button type="button" wire:click="$set('showNewClient', true)" wire:loading.attr="disabled" class="btn btn-ghost shrink-0">New</button>
            </div>
            @if (count($client_results))
                <div class="absolute z-20 mt-1 w-full overflow-hidden rounded-xl border border-stone-200 bg-white shadow-lg">
                    @foreach ($client_results as $client)
                        <button type="button" wire:click="selectClient({{ $client['id'] }})"
                            wire:loading.attr="disabled" wire:target="selectClient({{ $client['id'] }})"
                            class="block w-full px-3 py-2 text-left text-sm hover:bg-sand disabled:cursor-wait disabled:opacity-55">
                            <span class="font-medium">{{ $client['name'] }}</span>
                            <span class="ml-2 text-xs text-stone-500">TRN {{ $client['trn'] }}</span>
                        </button>
                    @endforeach
                </div>
            @endif
        </div>
        <div>
            <label class="label">Customer ref. / project</label>
            <input wire:model="reference" class="field" placeholder="Villa, site, PO">
        </div>
        <div>
            <label class="label">Currency</label>
            <input class="field bg-sand" value="AED" disabled>
        </div>
        <div>
            <label class="label">Delivery</label>
            <input wire:model="delivery" class="field">
        </div>
        <div>
            <label class="label">Payment terms</label>
            <input wire:model="payment_terms" class="field">
        </div>
        <div>
            <label class="label">Delivery availability</label>
            <input wire:model="delivery_availability" class="field">
        </div>
        <div class="xl:col-span-4">
            <label class="label">Delivery details</label>
            <textarea wire:model="delivery_details" rows="3" class="field"></textarea>
        </div>
    </div>

    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
        <h2 class="font-medium">Line items</h2>
        <button type="button" wire:click="addLine" wire:loading.attr="disabled" wire:target="addLine" class="btn btn-primary py-1.5 text-xs">
            <x-spinner class="h-3.5 w-3.5" wire:loading wire:target="addLine" />
            + Add product
        </button>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="quote-table">
                <thead>
                    <tr>
                        <th class="w-14">Image</th>
                        <th class="w-36">Heading</th>
                        <th class="w-36">Code</th>
                        <th class="min-w-[18rem]">Description</th>
                        <th class="w-16">Unit</th>
                        <th class="w-20">Qty</th>
                        <th class="w-28">Unit price</th>
                        <th class="w-20">Disc %</th>
                        <th class="w-24">Net</th>
                        <th class="w-24">VAT</th>
                        <th class="w-28">Inc. VAT</th>
                        <th class="w-28"></th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $lastHeading = null;
                        $carriedHeading = '';
                    @endphp
                    @forelse ($lines as $i => $item)
                        @php
                            $typedHeading = strtoupper(trim((string) ($item['heading'] ?? '')));
                            if ($typedHeading !== '') {
                                $carriedHeading = $typedHeading;
                            }
                            $heading = $carriedHeading;
                        @endphp
                        @if ($heading !== '' && $heading !== $lastHeading)
                            <tr class="quote-head" wire:key="heading-band-{{ $i }}">
                                <td colspan="8" class="px-3 py-2 text-sm font-semibold uppercase tracking-wide">{{ $heading }}</td>
                                <td colspan="4" class="px-3 py-2 text-right text-[10px] uppercase tracking-wide text-teal-100">Section heading</td>
                            </tr>
                        @endif
                        <tr class="border-b border-stone-200 bg-white align-top hover:bg-sand/40" wire:key="line-{{ $i }}">
                            <td class="px-2 py-1.5">
                                <label class="relative flex h-10 w-10 cursor-pointer items-center justify-center overflow-hidden rounded bg-sand" title="Upload image">
                                    @if (! empty($item['image']))
                                        <img src="{{ $item['image'] }}" alt="" class="h-10 w-10 object-cover">
                                    @else
                                        <span class="text-[9px] text-stone-400">Add</span>
                                    @endif
                                    <span wire:loading wire:target="linePhotos.{{ $i }}" class="absolute inset-0 flex items-center justify-center bg-white/70 text-zenora">
                                        <x-spinner class="h-4 w-4" />
                                    </span>
                                    <input type="file" accept="image/*" class="absolute inset-0 cursor-pointer opacity-0" wire:model="linePhotos.{{ $i }}">
                                </label>
                                @error('linePhotos.'.$i)
                                    <p class="mt-0.5 max-w-[4.5rem] text-[9px] leading-tight text-red-600">{{ $message }}</p>
                                @enderror
                            </td>
                            <td class="px-1 py-1 {{ $typedHeading !== '' ? 'bg-teal-50' : '' }}">
                                <input wire:model.blur="lines.{{ $i }}.heading" class="sheet-input uppercase {{ $typedHeading !== '' ? 'font-semibold text-zenora-dark' : '' }}" placeholder="{{ $typedHeading === '' && $heading !== '' ? $heading : 'Optional' }}">
                            </td>
                            <td class="px-1 py-1">
                                <div class="flex items-center gap-1">
                                    <input wire:model.live.debounce.200ms="lines.{{ $i }}.code" class="sheet-input font-semibold uppercase" placeholder="Type code">
                                    @if ($item['code'])
                                        <button type="button" x-data x-on:click="navigator.clipboard.writeText(@js($item['code']))" class="text-[10px] text-emerald-700" title="Copy code">⧉</button>
                                    @endif
                                </div>
                            </td>
                            <td class="px-1 py-1">
                                <textarea wire:model.blur="lines.{{ $i }}.description" rows="1" data-autosize
                                    class="sheet-input sheet-description" placeholder="Description"></textarea>
                            </td>
                            <td class="px-1 py-1">
                                <input wire:model.blur="lines.{{ $i }}.unit" class="sheet-input uppercase">
                            </td>
                            <td class="px-1 py-1">
                                <input type="text" inputmode="decimal" wire:model.live.debounce.200ms="lines.{{ $i }}.qty" class="sheet-input text-right" placeholder="1" onfocus="this.select()">
                            </td>
                            <td class="px-1 py-1">
                                <input type="text" inputmode="decimal" wire:model.live.debounce.200ms="lines.{{ $i }}.selling_price" class="sheet-input text-right" placeholder="0.00" onfocus="this.select()">
                            </td>
                            <td class="px-1 py-1">
                                <input type="text" inputmode="decimal" wire:model.live.debounce.200ms="lines.{{ $i }}.discount_percent" class="sheet-input text-right" placeholder="0" onfocus="this.select()">
                            </td>
                            <td class="px-2 py-2 text-right text-stone-600">{{ number_format((float) ($item['net'] ?? $item['amount']), 2) }}</td>
                            <td class="px-2 py-2 text-right text-stone-600">{{ number_format((float) $item['vat_amount'], 2) }}</td>
                            <td class="px-2 py-2 text-right font-semibold">{{ number_format((float) $item['line_total'], 2) }}</td>
                            <td class="whitespace-nowrap px-2 py-2 text-right">
                                <button type="button" wire:click="addLine({{ $i }})" wire:loading.attr="disabled" wire:target="addLine({{ $i }})" class="inline-flex items-center text-[10px] text-zenora disabled:cursor-wait disabled:opacity-55" title="Insert row below">+</button>
                                <button type="button" wire:click="duplicateLine({{ $i }})" wire:loading.attr="disabled" wire:target="duplicateLine({{ $i }})" class="ml-1 inline-flex items-center gap-0.5 text-[10px] text-zenora disabled:cursor-wait disabled:opacity-55">
                                    <x-spinner class="h-3 w-3" wire:loading wire:target="duplicateLine({{ $i }})" />
                                    Copy
                                </button>
                                <button type="button" wire:click="removeLine({{ $i }})" wire:loading.attr="disabled" wire:target="removeLine({{ $i }})" class="ml-1 inline-flex items-center text-[10px] text-red-600 disabled:cursor-wait disabled:opacity-55">×</button>
                            </td>
                        </tr>
                        @php $lastHeading = $heading; @endphp
                        @if (! empty($suggestions[$i]))
                            <tr class="bg-sand/70" wire:key="suggest-{{ $i }}">
                                <td colspan="12" class="px-3 py-2">
                                    <p class="mb-1 text-[10px] uppercase tracking-wide text-stone-500">Select a product</p>
                                    <div class="flex flex-col gap-1">
                                        @foreach ($suggestions[$i] as $suggestion)
                                            <button type="button" wire:click="pickProduct({{ $i }}, {{ $suggestion['id'] }})"
                                                wire:loading.attr="disabled" wire:target="pickProduct({{ $i }}, {{ $suggestion['id'] }})"
                                                class="rounded-lg bg-white px-3 py-2 text-left hover:bg-teal-50 disabled:cursor-wait disabled:opacity-55">
                                                <span class="font-semibold">{{ $suggestion['code'] }}</span>
                                                <span class="text-stone-500"> — {{ $suggestion['description'] }} · {{ aed($suggestion['selling_price']) }}</span>
                                            </button>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="12" class="px-4 py-6 text-center text-stone-500">
                                No products yet.
                                <button type="button" wire:click="addLine" wire:loading.attr="disabled" wire:target="addLine" class="inline-flex items-center gap-1 text-zenora underline disabled:cursor-wait disabled:opacity-55">
                                    <x-spinner class="h-3 w-3" wire:loading wire:target="addLine" />
                                    Add product
                                </button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5 grid gap-4 md:grid-cols-2">
        <div>
            <label class="label">Internal notes</label>
            <textarea wire:model="notes" rows="4" class="field"></textarea>
        </div>
        <div>
            <label class="label">Terms (printed on PDF)</label>
            <textarea wire:model="terms" rows="4" class="field"></textarea>
        </div>
    </div>

    @if ($showNewClient)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-ink/40 p-4">
            <div class="card w-full max-w-lg p-6">
                <h3 class="font-display text-xl">New client</h3>
                <div class="mt-4 space-y-3">
                    <div>
                        <label class="label">Name</label>
                        <input wire:model="nc_name" class="field">
                        @error('nc_name') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label">TRN (required)</label>
                        <input wire:model="nc_trn" class="field">
                        @error('nc_trn') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label">Phone</label>
                            <input wire:model="nc_phone" class="field">
                        </div>
                        <div>
                            <label class="label">Email</label>
                            <input wire:model="nc_email" class="field">
                        </div>
                    </div>
                    <div>
                        <label class="label">Address</label>
                        <input wire:model="nc_address" class="field">
                    </div>
                    <div>
                        <label class="label">Emirate</label>
                        <select wire:model="nc_emirate" class="field">
                            <option value="">Select</option>
                            @foreach (zenora_emirates() as $emirate)
                                <option value="{{ $emirate }}">{{ $emirate }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="mt-5 flex gap-2">
                    <button type="button" wire:click="saveNewClient" wire:loading.attr="disabled" wire:target="saveNewClient" class="btn btn-primary">
                        <x-spinner class="h-4 w-4" wire:loading wire:target="saveNewClient" />
                        <span wire:loading.remove wire:target="saveNewClient">Save client</span>
                        <span wire:loading wire:target="saveNewClient">Saving…</span>
                    </button>
                    <button type="button" wire:click="$set('showNewClient', false)" wire:loading.attr="disabled" wire:target="saveNewClient" class="btn btn-ghost">Cancel</button>
                </div>
            </div>
        </div>
    @endif
</div>
