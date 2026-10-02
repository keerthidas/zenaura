<div>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs uppercase tracking-[0.2em] text-stone-500">Purchasing</p>
            <h1 class="font-display text-3xl">Local purchase orders</h1>
        </div>
        <button type="button" wire:click="newLpo" wire:loading.attr="disabled" wire:target="newLpo" class="btn btn-copper">
            <x-spinner class="h-4 w-4" wire:loading wire:target="newLpo" />
            New LPO{{ $filterQuote ? ' for '.$filterQuote->number : '' }}
        </button>
    </div>

    @if ($justIssued)
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-zenora-dark">
            <p>LPO <strong>{{ $justIssued->number }}</strong> is ready.</p>
            <a href="{{ route('purchase-orders.pdf', $justIssued) }}" target="_blank" class="btn btn-primary py-1.5 text-xs">Open PDF</a>
        </div>
    @endif

    @if ($filterQuote)
        <div class="card mb-4 flex flex-wrap items-center justify-between gap-3 px-5 py-4">
            <div>
                <p class="text-xs uppercase tracking-[0.18em] text-stone-500">Quotation</p>
                <p class="font-display text-xl">{{ $filterQuote->number }}</p>
                <p class="text-sm text-stone-500">{{ $filterQuote->client?->name }} · Quoted {{ aed($filterQuote->grand_total) }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-6 text-sm">
                <div class="text-right">
                    <p class="text-xs uppercase tracking-wide text-stone-500">Active LPOs</p>
                    <p class="font-semibold">{{ $filterSummary['active'] }}</p>
                </div>
                <div class="text-right">
                    <p class="text-xs uppercase tracking-wide text-stone-500">Ordered (inc. VAT)</p>
                    <p class="font-semibold">{{ aed($filterSummary['value']) }}</p>
                </div>
                <button type="button" wire:click="clearQuotation" class="text-zenora hover:underline">Show all LPOs</button>
            </div>
        </div>
    @endif

    <div class="mb-4 flex flex-wrap gap-3">
        <div class="relative max-w-md flex-1">
            <input wire:model.live.debounce.300ms="search" class="field pr-9" placeholder="Search LPO no., supplier, quotation">
            <span wire:loading wire:target="search" class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-zenora">
                <x-spinner class="h-4 w-4" />
            </span>
        </div>
        <div class="flex flex-wrap gap-1">
            @foreach (['' => 'All', 'issued' => 'Active', 'cancelled' => 'Cancelled'] as $value => $label)
                <button wire:click="setStatus('{{ $value }}')" type="button"
                    class="rounded-full px-3 py-1.5 text-xs {{ $status === $value ? 'bg-zenora-dark text-white' : 'bg-white border border-stone-200' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    <div class="card relative overflow-hidden">
        <div wire:loading.flex wire:target="setStatus,reissue,confirmCancel,clearQuotation,delete"
            class="absolute inset-0 z-10 items-center justify-center bg-white/70">
            <x-spinner class="h-7 w-7 text-zenora" />
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-sand/60 text-left text-xs uppercase tracking-wide text-stone-500">
                    <tr>
                        <th class="px-4 py-3">LPO no.</th>
                        <th class="px-4 py-3">Supplier</th>
                        <th class="px-4 py-3">Quotation</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Total</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr wire:key="lpo-{{ $order->id }}" class="border-t border-stone-100 hover:bg-sand/30 {{ $open === $order->id ? 'bg-teal-50/60' : '' }}">
                            <td class="px-4 py-3">
                                <p class="font-semibold {{ $order->isCancelled() ? 'text-stone-400 line-through' : '' }}">{{ $order->number }}</p>
                                @if ($order->replaces)
                                    <p class="mt-0.5 text-[11px] text-stone-500">Replaces {{ $order->replaces->number }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $order->supplier?->name }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('purchase-orders.index', ['quotation' => $order->quotation_id]) }}" class="hover:text-zenora" wire:navigate>{{ $order->quotation?->number }}</a>
                                <p class="text-[11px] text-stone-500">{{ $order->quotation?->client?->name }}</p>
                            </td>
                            <td class="px-4 py-3">{{ $order->lpo_date->format('d M Y') }}</td>
                            <td class="px-4 py-3">
                                @if ($order->isIssued())
                                    <span class="rounded-full bg-teal-50 px-2 py-0.5 text-xs text-zenora-dark">Active</span>
                                @else
                                    <span class="rounded-full bg-red-50 px-2 py-0.5 text-xs text-red-700">Cancelled</span>
                                    <p class="mt-1 max-w-[16rem] text-[11px] text-stone-500">
                                        {{ $order->cancelled_at?->format('d M Y') }}@if ($order->cancel_reason) · {{ $order->cancel_reason }}@endif
                                    </p>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right {{ $order->isCancelled() ? 'text-stone-400' : '' }}">{{ aed($order->grand_total) }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('purchase-orders.pdf', $order) }}" target="_blank" class="text-zenora hover:underline">PDF</a>
                                @if ($order->isIssued())
                                    <button type="button" wire:click="reissue({{ $order->id }})" class="ml-2 text-zenora hover:underline"
                                        title="Open a copy with editable prices; this LPO is cancelled when the new one is issued">Cancel &amp; re-issue</button>
                                    <button type="button" wire:click="startCancel({{ $order->id }})" class="ml-2 text-red-600 hover:underline">Cancel</button>
                                @endif
                                <button type="button" wire:click="delete({{ $order->id }})"
                                    wire:confirm="Delete LPO {{ $order->number }}? It will be removed from the list and its quantities become available again."
                                    class="ml-2 text-red-600 hover:underline">Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-stone-500">
                                No LPOs yet. Click <strong>New LPO</strong> to raise one from a quotation.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-stone-100 px-4 py-3">{{ $orders->links() }}</div>
    </div>

    @if ($cancelling)
        <div class="fixed inset-0 z-40 flex items-start justify-center overflow-y-auto bg-ink/40 p-4 sm:items-center">
            <div class="card my-6 w-full max-w-md p-6">
                <h3 class="font-display text-2xl">Cancel {{ $cancelling->number }}?</h3>
                <p class="mt-1 text-sm text-stone-500">{{ $cancelling->supplier?->name }} · {{ aed($cancelling->grand_total) }}. The LPO stays on record marked as cancelled and its number is not reused.</p>
                <div class="mt-4">
                    <label class="label">Reason (optional)</label>
                    <input wire:model="cancelReason" class="field" placeholder="e.g. Supplier revised price">
                    @error('cancelReason') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="mt-5 flex flex-wrap gap-2">
                    <button type="button" wire:click="confirmCancel" wire:loading.attr="disabled" wire:target="confirmCancel" class="btn bg-red-600 text-white hover:bg-red-700">
                        <x-spinner class="h-4 w-4" wire:loading wire:target="confirmCancel" />
                        Cancel LPO
                    </button>
                    <button type="button" wire:click="closeCancel" class="btn btn-ghost">Keep it</button>
                </div>
            </div>
        </div>
    @endif

    @if ($showPicker)
        <div class="fixed inset-0 z-40 flex items-start justify-center overflow-y-auto bg-ink/40 p-4 sm:items-center">
            <div class="card my-6 w-full max-w-xl p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="font-display text-2xl">Which quotation?</h3>
                        <p class="mt-1 text-sm text-stone-500">An LPO is raised against a quotation. Pick one to load its lines.</p>
                    </div>
                    <button type="button" wire:click="$set('showPicker', false)" class="btn btn-ghost py-1.5 text-sm">Close</button>
                </div>
                <input wire:model.live.debounce.250ms="pickerSearch" class="field mt-4" placeholder="Search quotation number, client, reference" autofocus>
                <div class="mt-3 overflow-hidden rounded-xl border border-stone-200">
                    @forelse ($pickerResults as $pick)
                        <a href="{{ route('purchase-orders.create', ['quotation' => $pick->id]) }}" wire:navigate
                            class="flex items-center justify-between gap-3 border-t border-stone-100 px-4 py-3 first:border-t-0 hover:bg-sand/40">
                            <div class="min-w-0">
                                <p class="font-semibold">{{ $pick->number }}</p>
                                <p class="truncate text-xs text-stone-500">{{ $pick->client?->name }} · {{ $pick->quote_date->format('d M Y') }} · <span class="capitalize">{{ $pick->status }}</span></p>
                            </div>
                            <div class="shrink-0 text-right text-sm">
                                <p>{{ aed($pick->grand_total) }}</p>
                                @if ($pick->purchase_orders_count)
                                    <p class="text-[11px] text-copper">{{ $pick->purchase_orders_count }} active LPO{{ $pick->purchase_orders_count === 1 ? '' : 's' }}</p>
                                @endif
                            </div>
                        </a>
                    @empty
                        <p class="px-4 py-6 text-center text-sm text-stone-500">No quotations match.</p>
                    @endforelse
                </div>
            </div>
        </div>
    @endif
</div>
