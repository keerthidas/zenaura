<div>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs uppercase tracking-[0.2em] text-stone-500">Sales</p>
            <h1 class="font-display text-3xl">Quotations</h1>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('delivery-notes.index') }}" class="btn btn-ghost" wire:navigate>Delivery notes</a>
            <a href="{{ route('quotations.create') }}" class="btn btn-copper" wire:navigate>New quotation</a>
        </div>
    </div>

    <div class="mb-4 flex flex-wrap gap-3">
        <div class="relative max-w-md flex-1">
            <input wire:model.live.debounce.300ms="search" class="field pr-9" placeholder="Search number, client, TRN">
            <span wire:loading wire:target="search" class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-zenora">
                <x-spinner class="h-4 w-4" />
            </span>
        </div>
        <div class="flex flex-wrap gap-1">
            @foreach (['' => 'All', 'draft' => 'Draft', 'sent' => 'Sent', 'accepted' => 'Accepted', 'rejected' => 'Rejected', 'expired' => 'Expired'] as $value => $label)
                <button wire:click="setStatus('{{ $value }}')" type="button"
                    wire:loading.attr="disabled" wire:target="setStatus"
                    class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs disabled:cursor-wait disabled:opacity-55 {{ $status === $value ? 'bg-zenora-dark text-white' : 'bg-white border border-stone-200' }}">
                    <x-spinner class="h-3 w-3" wire:loading wire:target="setStatus('{{ $value }}')" />
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    <div class="card relative overflow-hidden">
        <div wire:loading.flex wire:target="setStatus,revise,duplicate,delete,updateStatus,openProforma,createProforma,deleteProforma,openReceipt,createReceipt"
            class="absolute inset-0 z-10 items-center justify-center bg-white/70">
            <x-spinner class="h-7 w-7 text-zenora" />
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-sand/60 text-left text-xs uppercase tracking-wide text-stone-500">
                    <tr>
                        <th class="px-4 py-3">Number</th>
                        <th class="px-4 py-3">Client</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Total</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($quotations as $quote)
                        <tr class="border-t border-stone-100 hover:bg-sand/30">
                            <td class="px-4 py-3">
                                <a href="{{ $quote->isDraft() ? route('quotations.edit', $quote) : route('quotations.create', ['revise' => $quote->id]) }}" class="font-semibold hover:text-zenora" wire:navigate>{{ $quote->number }}</a>
                                @if ($quote->revision)
                                    <span class="ml-1 rounded bg-sand px-1.5 py-0.5 text-[10px] uppercase text-copper">{{ $quote->revisionLabel() }}</span>
                                @endif
                                @if ($quote->invoice_number)
                                    <div class="mt-0.5 text-[11px] text-stone-500">{{ $quote->invoice_number }}</div>
                                @endif
                                @if ($quote->proformas->isNotEmpty())
                                    <div class="mt-1 text-[11px] text-stone-500">
                                        {{ $quote->proformas->count() }} proforma{{ $quote->proformas->count() === 1 ? '' : 's' }}
                                        · {{ rtrim(rtrim(number_format($quote->proformaPercentInvoiced(), 2, '.', ''), '0'), '.') }}% billed
                                    </div>
                                @endif
                                @if ($quote->active_purchase_orders_count)
                                    <div class="mt-0.5 text-[11px] text-stone-500">
                                        {{ $quote->active_purchase_orders_count }} active LPO{{ $quote->active_purchase_orders_count === 1 ? '' : 's' }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $quote->client?->name }}</td>
                            <td class="px-4 py-3">{{ $quote->quote_date->format('d M Y') }}</td>
                            <td class="px-4 py-3">
                                <select wire:change="updateStatus({{ $quote->id }}, $event.target.value)"
                                    wire:loading.attr="disabled" wire:target="updateStatus({{ $quote->id }})"
                                    class="field-compact w-32 capitalize disabled:cursor-wait disabled:opacity-55">
                                    @foreach (\App\Models\Quotation::STATUSES as $item)
                                        <option value="{{ $item }}" @selected($quote->status === $item)>{{ $item }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-4 py-3 text-right">{{ aed($quote->grand_total) }}</td>
                            <td class="px-4 py-3 text-right">
                                <div x-data="{
                                    open: false,
                                    top: 0,
                                    right: 0,
                                    toggleMenu(button) {
                                        this.open = ! this.open;
                                        if (! this.open) return;
                                        const rect = button.getBoundingClientRect();
                                        this.top = rect.bottom + 4;
                                        this.right = window.innerWidth - rect.right;
                                        this.$nextTick(() => {
                                            const height = this.$refs.menu?.offsetHeight ?? 0;
                                            if (this.top + height > window.innerHeight - 8) this.top = Math.max(8, rect.top - height - 4);
                                        });
                                    },
                                }" x-on:scroll.window="open = false" x-on:resize.window="open = false" x-on:keydown.escape.window="open = false" class="relative inline-block text-left">
                                    <button type="button" x-on:click="toggleMenu($el)" :aria-expanded="open" aria-haspopup="menu" aria-label="Actions for {{ $quote->number }}" title="Actions"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-stone-200 bg-white text-stone-600 hover:bg-sand hover:text-ink focus:outline-none focus:ring-2 focus:ring-zenora/30">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                            <circle cx="5" cy="12" r="1.7" />
                                            <circle cx="12" cy="12" r="1.7" />
                                            <circle cx="19" cy="12" r="1.7" />
                                        </svg>
                                    </button>
                                    <template x-teleport="body">
                                    <div x-ref="menu" x-cloak x-show="open" x-transition.origin.top.right x-on:click.outside="open = false" :style="`position:fixed;top:${top}px;right:${right}px`" role="menu"
                                        class="z-[100] max-h-[70vh] w-48 overflow-y-auto rounded-lg border border-stone-200 bg-white py-1 text-left shadow-lg">
                                        <button type="button" role="menuitem" x-on:click="open = false; $dispatch('open-quotation-pdf', { url: @js(route('quotations.pdf', $quote)) })" class="block w-full px-3 py-2 text-left text-sm text-ink hover:bg-sand">Quotation PDF</button>
                                        <button type="button" role="menuitem" wire:click="openProforma({{ $quote->id }})" x-on:click="open = false"
                                            wire:loading.attr="disabled" wire:target="openProforma({{ $quote->id }})" class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-ink hover:bg-sand disabled:opacity-55">
                                            <x-spinner class="h-3 w-3" wire:loading wire:target="openProforma({{ $quote->id }})" /> Proforma
                                        </button>
                                        <a role="menuitem" href="{{ route('purchase-orders.index', ['quotation' => $quote->id]) }}" wire:navigate x-on:click="open = false" class="block px-3 py-2 text-sm text-ink hover:bg-sand">LPOs</a>
                                        @if ($quote->status === 'accepted')
                                            <a role="menuitem" href="{{ route('delivery-notes.create', ['quotation' => $quote->id]) }}" wire:navigate x-on:click="open = false" class="block px-3 py-2 text-sm text-ink hover:bg-sand">Delivery note</a>
                                            <a role="menuitem" href="{{ route('quotations.invoice', $quote) }}" target="_blank" x-on:click="open = false" class="block px-3 py-2 text-sm text-ink hover:bg-sand">Tax invoice</a>
                                        @endif
                                        @if ($quote->isDraft())
                                            <a role="menuitem" href="{{ route('quotations.edit', $quote) }}" wire:navigate x-on:click="open = false" class="block px-3 py-2 text-sm text-ink hover:bg-sand">Edit</a>
                                        @else
                                            <button type="button" role="menuitem" wire:click="revise({{ $quote->id }})" x-on:click="open = false"
                                                wire:loading.attr="disabled" wire:target="revise({{ $quote->id }})" class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-ink hover:bg-sand disabled:opacity-55">
                                                <x-spinner class="h-3 w-3" wire:loading wire:target="revise({{ $quote->id }})" /> Revise
                                            </button>
                                        @endif
                                        <button type="button" role="menuitem" wire:click="duplicate({{ $quote->id }})" x-on:click="open = false"
                                            wire:loading.attr="disabled" wire:target="duplicate({{ $quote->id }})" class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-ink hover:bg-sand disabled:opacity-55">
                                            <x-spinner class="h-3 w-3" wire:loading wire:target="duplicate({{ $quote->id }})" /> Copy quotation
                                        </button>
                                        <div class="my-1 border-t border-stone-100"></div>
                                        <button type="button" role="menuitem" wire:click="delete({{ $quote->id }})" x-on:click="open = false" wire:confirm="Archive this quotation?"
                                            wire:loading.attr="disabled" wire:target="delete({{ $quote->id }})" class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-red-700 hover:bg-red-50 disabled:opacity-55">
                                            <x-spinner class="h-3 w-3" wire:loading wire:target="delete({{ $quote->id }})" /> Archive quotation
                                        </button>
                                    </div>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-stone-500">No quotations yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-stone-100 px-4 py-3">{{ $quotations->links() }}</div>
    </div>

    @if ($proformaQuote)
        @php
            $fmtPct = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, '.', ''), '0'), '.');
            $invoicedPct = $proformaQuote->proformaPercentInvoiced();
            $remainingPct = $proformaQuote->proformaRemainingPercent();
            $invoicedAmt = (float) $proformaQuote->proformas->sum('grand_total');
            $openAmt = (float) $proformaQuote->grand_total - $invoicedAmt;
            $receiptProforma = $receiptProformaId
                ? $proformaQuote->proformas->firstWhere('id', $receiptProformaId)
                : null;
            $isCreating = $showProformaForm && $remainingPct > 0;
            $typedPct = is_numeric($proformaPercent) ? max(0, (float) $proformaPercent) : 0;
            $previewAmt = round(((float) $proformaQuote->grand_total) * $typedPct / 100, 2);
        @endphp
        <div class="fixed inset-0 z-40 flex items-start justify-center overflow-y-auto bg-ink/40 p-4 sm:items-center">
            <div class="card my-6 w-full max-w-xl p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs uppercase tracking-[0.18em] text-stone-500">Billing against quotation</p>
                        <h3 class="mt-1 font-display text-2xl">{{ $proformaQuote->number }}</h3>
                        <p class="mt-1 text-sm text-stone-500">{{ $proformaQuote->client?->name }} · {{ aed($proformaQuote->grand_total) }}</p>
                    </div>
                    <button type="button" wire:click="closeProforma" class="btn btn-ghost py-1.5 text-sm">Close</button>
                </div>

                <div class="mt-5">
                    <div class="flex items-center justify-between gap-3 text-sm">
                        <p><span class="font-semibold">{{ $fmtPct($invoicedPct) }}%</span> billed · {{ aed($invoicedAmt) }}</p>
                        <p class="text-stone-500">{{ $fmtPct($remainingPct) }}% open · {{ aed($openAmt) }}</p>
                    </div>
                    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-sand">
                        <div class="h-1.5 rounded-full bg-zenora-dark" style="width: {{ min(100, max(0, $invoicedPct)) }}%"></div>
                    </div>
                </div>

                @if ($isCreating)
                    <div class="mt-6">
                        @if ($proformaQuote->proformas->isNotEmpty())
                            <button type="button" wire:click="cancelNewProforma" class="text-sm text-zenora hover:underline">← Back to proformas</button>
                        @endif
                        <h4 class="{{ $proformaQuote->proformas->isNotEmpty() ? 'mt-3' : '' }} font-medium">New proforma</h4>
                        <p class="mt-1 text-sm text-stone-500">Invoice a percentage of this quotation. {{ $fmtPct($remainingPct) }}% can still be billed.</p>

                        <div class="mt-4 space-y-4">
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="label">Percent to invoice</label>
                                    <input type="text" inputmode="decimal" wire:model.live.debounce.200ms="proformaPercent" class="field" placeholder="50">
                                    @error('proformaPercent') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="label">This proforma</label>
                                    <p class="field flex items-center bg-sand/70 font-medium shadow-none">{{ aed($previewAmt) }}</p>
                                </div>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="label">Payment terms</label>
                                    <input wire:model="proformaPaymentTerms" class="field">
                                    @error('proformaPaymentTerms') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="label">Delivery terms</label>
                                    <input wire:model="proformaDeliveryTerms" class="field">
                                    @error('proformaDeliveryTerms') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <div>
                                <label class="label">Client LPO Reference No. (optional)</label>
                                <input wire:model="proformaClientLpoReferenceNo" class="field" maxlength="100">
                                @error('proformaClientLpoReferenceNo') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="label">Description</label>
                                <textarea wire:model="proformaDescription" rows="3" class="field"></textarea>
                                @error('proformaDescription') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div class="mt-5 flex gap-2">
                            <button type="button" wire:click="createProforma" wire:loading.attr="disabled" wire:target="createProforma" class="btn btn-primary">
                                <x-spinner class="h-4 w-4" wire:loading wire:target="createProforma" />
                                <span wire:loading.remove wire:target="createProforma">Generate proforma</span>
                                <span wire:loading wire:target="createProforma">Generating…</span>
                            </button>
                            @if ($proformaQuote->proformas->isNotEmpty())
                                <button type="button" wire:click="cancelNewProforma" class="btn btn-ghost">Cancel</button>
                            @endif
                        </div>
                    </div>
                @elseif ($receiptProforma)
                    <div class="mt-6">
                        <button type="button" wire:click="closeReceipt" class="text-sm text-zenora hover:underline">← Back to proformas</button>
                        <h4 class="mt-3 font-medium">Record receipt</h4>
                        <p class="mt-1 text-sm text-stone-500">{{ $receiptProforma->number }} · {{ $receiptProforma->percentLabel() }} · {{ aed($receiptProforma->grand_total) }}</p>

                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="label">Received date</label>
                                <input type="date" wire:model="receiptDate" class="field">
                                @error('receiptDate') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="label">Payment mode</label>
                                <select wire:model="receiptPaymentMode" class="field">
                                    @foreach (\App\Models\ReceiptVoucher::PAYMENT_MODES as $mode)
                                        <option value="{{ $mode }}">{{ $mode }}</option>
                                    @endforeach
                                </select>
                                @error('receiptPaymentMode') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label class="label">Reference (optional)</label>
                                <input wire:model="receiptReference" class="field" placeholder="Cheque no. / transfer ref.">
                                @error('receiptReference') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div class="mt-5 flex gap-2">
                            <button type="button" wire:click="createReceipt" wire:loading.attr="disabled" wire:target="createReceipt" class="btn btn-primary">
                                <x-spinner class="h-4 w-4" wire:loading wire:target="createReceipt" />
                                <span wire:loading.remove wire:target="createReceipt">Save receipt</span>
                                <span wire:loading wire:target="createReceipt">Saving…</span>
                            </button>
                            <button type="button" wire:click="closeReceipt" class="btn btn-ghost">Cancel</button>
                        </div>
                    </div>
                @else
                    <div class="mt-6">
                        <div class="mb-3 flex items-center justify-between gap-2">
                            <h4 class="font-medium">Proformas</h4>
                            @if ($remainingPct > 0)
                                <button type="button" wire:click="startNewProforma" class="btn btn-primary py-1.5 text-xs">New proforma</button>
                            @endif
                        </div>

                        @if ($proformaQuote->proformas->isEmpty())
                            <p class="rounded-xl border border-dashed border-stone-200 px-4 py-8 text-center text-sm text-stone-500">No proformas yet. Create one to invoice a percentage of this quotation.</p>
                        @else
                            <div class="overflow-hidden rounded-xl border border-stone-200">
                                @foreach ($proformaQuote->proformas as $proforma)
                                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-stone-100 px-4 py-3 first:border-t-0">
                                        <div class="min-w-0">
                                            <p class="font-semibold">{{ $proforma->number }}</p>
                                            <p class="mt-0.5 text-sm text-stone-500">{{ $proforma->percentLabel() }} · {{ aed($proforma->grand_total) }} · {{ optional($proforma->invoice_date)->format('d M Y') }}</p>
                                            @if ($proforma->receipt)
                                                <p class="mt-1 text-xs text-zenora-dark">Paid · {{ $proforma->receipt->number }} · {{ $proforma->receipt->payment_mode }}</p>
                                            @else
                                                <p class="mt-1 text-xs text-copper">Awaiting payment</p>
                                            @endif
                                        </div>
                                        <div class="flex shrink-0 flex-wrap items-center gap-2">
                                            <a href="{{ route('quotations.proforma', [$proformaQuote, $proforma]) }}" target="_blank" class="btn btn-ghost px-3 py-1.5 text-xs">PDF</a>
                                            @if ($proforma->receipt)
                                                <a href="{{ route('quotations.receipt', [$proformaQuote, $proforma, $proforma->receipt]) }}" target="_blank" class="btn btn-ghost px-3 py-1.5 text-xs">Receipt</a>
                                            @else
                                                <button type="button" wire:click="openReceipt({{ $proforma->id }})" class="btn btn-primary px-3 py-1.5 text-xs">Record receipt</button>
                                            @endif
                                            <button type="button" wire:click="deleteProforma({{ $proforma->id }})"
                                                wire:confirm="Delete this proforma? Its receipt will be removed and the percent will be available again."
                                                class="btn btn-ghost px-3 py-1.5 text-xs text-red-600 hover:border-red-200 hover:bg-red-50">Delete</button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if ($remainingPct <= 0)
                            <p class="mt-3 text-sm text-stone-500">This quotation is fully covered (100%). Open a PDF or receipt from the list above.</p>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
