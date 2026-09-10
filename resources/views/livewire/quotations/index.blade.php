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
        <div wire:loading.flex wire:target="setStatus,revise,duplicate,delete,updateStatus"
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
                                @if ($quote->status === 'accepted')
                                    <a href="{{ route('quotations.invoice', $quote) }}" target="_blank" class="ml-2 text-zenora hover:underline">Invoice</a>
                                @endif
                                @if (! $quote->isDraft())
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
</div>
