<div>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs uppercase tracking-[0.2em] text-stone-500">Sales</p>
            <h1 class="font-display text-3xl">Quotations</h1>
        </div>
        <a href="{{ route('quotations.create') }}" class="btn btn-copper" wire:navigate>New quotation</a>
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
                                @foreach ($quote->proformas as $proforma)
                                    <div class="mt-0.5 text-[11px] text-stone-500">
                                        <a href="{{ route('quotations.proforma', [$quote, $proforma]) }}" target="_blank" class="hover:text-zenora hover:underline">{{ $proforma->number }}</a>
                                        <span>({{ $proforma->percentLabel() }})</span>
                                    </div>
                                @endforeach
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
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('quotations.pdf', $quote) }}" target="_blank" class="text-zenora hover:underline">PDF</a>
                                <button type="button" wire:click="openProforma({{ $quote->id }})"
                                    wire:loading.attr="disabled" wire:target="openProforma({{ $quote->id }})"
                                    class="ml-2 inline-flex items-center gap-1 text-zenora hover:underline disabled:cursor-wait disabled:opacity-55">
                                    <x-spinner class="h-3 w-3" wire:loading wire:target="openProforma({{ $quote->id }})" />
                                    Proforma
                                </button>
                                @if ($quote->status === 'accepted')
                                    <a href="{{ route('quotations.invoice', $quote) }}" target="_blank" class="ml-2 text-zenora hover:underline">Invoice</a>
                                @endif
                                @if ($quote->isDraft())
                                    <a href="{{ route('quotations.edit', $quote) }}" class="ml-2 text-zenora hover:underline" wire:navigate>Edit</a>
                                @else
                                    <button type="button" wire:click="revise({{ $quote->id }})"
                                        wire:loading.attr="disabled" wire:target="revise({{ $quote->id }})"
                                        class="ml-2 inline-flex items-center gap-1 text-zenora hover:underline disabled:cursor-wait disabled:opacity-55">
                                        <x-spinner class="h-3 w-3" wire:loading wire:target="revise({{ $quote->id }})" />
                                        Revise
                                    </button>
                                @endif
                                <button type="button" wire:click="duplicate({{ $quote->id }})"
                                    wire:loading.attr="disabled" wire:target="duplicate({{ $quote->id }})"
                                    class="ml-2 inline-flex items-center gap-1 text-zenora hover:underline disabled:cursor-wait disabled:opacity-55">
                                    <x-spinner class="h-3 w-3" wire:loading wire:target="duplicate({{ $quote->id }})" />
                                    Copy
                                </button>
                                <button type="button" wire:click="delete({{ $quote->id }})" wire:confirm="Archive this quotation?"
                                    wire:loading.attr="disabled" wire:target="delete({{ $quote->id }})"
                                    class="ml-2 inline-flex items-center gap-1 text-red-600 hover:underline disabled:cursor-wait disabled:opacity-55">
                                    <x-spinner class="h-3 w-3" wire:loading wire:target="delete({{ $quote->id }})" />
                                    Delete
                                </button>
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
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-ink/40 p-4">
            <div class="card w-full max-w-xl p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="font-display text-xl">Proforma invoice</h3>
                        <p class="mt-1 text-sm text-stone-500">{{ $proformaQuote->number }} · {{ aed($proformaQuote->grand_total) }}</p>
                    </div>
                    <button type="button" wire:click="closeProforma" class="text-sm text-stone-500 hover:text-ink">Close</button>
                </div>

                <p class="mt-3 text-sm">
                    Invoiced <span class="font-semibold">{{ rtrim(rtrim(number_format($proformaQuote->proformaPercentInvoiced(), 2, '.', ''), '0'), '.') }}%</span>
                    · Remaining <span class="font-semibold">{{ rtrim(rtrim(number_format($proformaQuote->proformaRemainingPercent(), 2, '.', ''), '0'), '.') }}%</span>
                </p>

                @if ($proformaQuote->proformas->isNotEmpty())
                    <div class="mt-3 space-y-2 rounded-xl bg-sand/70 px-3 py-2">
                        @foreach ($proformaQuote->proformas as $proforma)
                            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-white/70 py-2 last:border-b-0 last:pb-0 first:pt-0">
                                <div class="text-sm">
                                    <a href="{{ route('quotations.proforma', [$proformaQuote, $proforma]) }}" target="_blank" class="font-medium text-zenora hover:underline">{{ $proforma->number }}</a>
                                    <span class="text-stone-500"> · {{ $proforma->percentLabel() }} · {{ aed($proforma->grand_total) }}</span>
                                    @if ($proforma->receipt)
                                        <div class="text-[11px] text-stone-500">{{ $proforma->receipt->number }}</div>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2 text-xs">
                                    <a href="{{ route('quotations.proforma', [$proformaQuote, $proforma]) }}" target="_blank" class="text-zenora hover:underline">PDF</a>
                                    @if ($proforma->receipt)
                                        <a href="{{ route('quotations.receipt', [$proformaQuote, $proforma, $proforma->receipt]) }}" target="_blank" class="text-zenora hover:underline">Receipt</a>
                                    @else
                                        <button type="button" wire:click="openReceipt({{ $proforma->id }})"
                                            wire:loading.attr="disabled" wire:target="openReceipt({{ $proforma->id }})"
                                            class="text-zenora hover:underline disabled:cursor-wait disabled:opacity-55">Receipt</button>
                                    @endif
                                    <button type="button" wire:click="deleteProforma({{ $proforma->id }})"
                                        wire:confirm="Delete this proforma invoice? The remaining percent will be freed."
                                        wire:loading.attr="disabled" wire:target="deleteProforma({{ $proforma->id }})"
                                        class="text-red-600 hover:underline disabled:cursor-wait disabled:opacity-55">Delete</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if ($receiptProformaId)
                    @php $receiptProforma = $proformaQuote->proformas->firstWhere('id', $receiptProformaId); @endphp
                    @if ($receiptProforma)
                        <div class="mt-4 rounded-xl border border-stone-200 p-4">
                            <h4 class="font-medium">Receipt voucher for {{ $receiptProforma->number }}</h4>
                            <p class="mt-1 text-sm text-stone-500">Amount {{ aed($receiptProforma->grand_total) }}</p>
                            <div class="mt-3 grid gap-3 sm:grid-cols-2">
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
                            <div class="mt-4 flex gap-2">
                                <button type="button" wire:click="createReceipt" wire:loading.attr="disabled" wire:target="createReceipt" class="btn btn-primary">
                                    <x-spinner class="h-4 w-4" wire:loading wire:target="createReceipt" />
                                    <span wire:loading.remove wire:target="createReceipt">Generate receipt</span>
                                    <span wire:loading wire:target="createReceipt">Generating…</span>
                                </button>
                                <button type="button" wire:click="closeReceipt" class="btn btn-ghost">Cancel</button>
                            </div>
                        </div>
                    @endif
                @endif

                @if ($proformaQuote->proformaRemainingPercent() > 0)
                    <div class="mt-4 space-y-3">
                        <div>
                            <label class="label">Percent to invoice</label>
                            <input type="text" inputmode="decimal" wire:model="proformaPercent" class="field" placeholder="50">
                            @error('proformaPercent') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
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
                            <label class="label">Description</label>
                            <textarea wire:model="proformaDescription" rows="4" class="field"></textarea>
                            @error('proformaDescription') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="mt-5 flex gap-2">
                        <button type="button" wire:click="createProforma" wire:loading.attr="disabled" wire:target="createProforma" class="btn btn-primary">
                            <x-spinner class="h-4 w-4" wire:loading wire:target="createProforma" />
                            <span wire:loading.remove wire:target="createProforma">Generate proforma</span>
                            <span wire:loading wire:target="createProforma">Generating…</span>
                        </button>
                        <button type="button" wire:click="closeProforma" class="btn btn-ghost">Cancel</button>
                    </div>
                @else
                    <p class="mt-4 text-sm text-stone-500">This quotation is fully covered (100%). Open an existing proforma above.</p>
                    <div class="mt-5">
                        <button type="button" wire:click="closeProforma" class="btn btn-ghost">Close</button>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
