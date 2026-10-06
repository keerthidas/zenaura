<div class="mx-auto max-w-6xl">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs uppercase tracking-[0.2em] text-stone-500">Logistics</p>
            <h1 class="font-display text-3xl">Delivery notes</h1>
        </div>
        <a href="{{ route('delivery-notes.create') }}" class="btn btn-primary" wire:navigate>New delivery note</a>
    </div>

    <div class="mb-4 max-w-md">
        <input wire:model.live.debounce.300ms="search" class="field" placeholder="Search note, quotation, client or supplier">
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-sand/60 text-left text-xs uppercase tracking-wide text-stone-500">
                    <tr>
                        <th class="px-4 py-3">Delivery note</th>
                        <th class="px-4 py-3">Quotation / client</th>
                        <th class="px-4 py-3">Supplier</th>
                        <th class="px-4 py-3">Delivery date</th>
                        <th class="px-4 py-3 text-right">Materials</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($deliveryNotes as $deliveryNote)
                        <tr class="border-t border-stone-100 hover:bg-sand/30">
                            <td class="px-4 py-3 font-semibold">{{ $deliveryNote->number }}</td>
                            <td class="px-4 py-3">
                                <p>{{ $deliveryNote->quotation?->number }}</p>
                                <p class="text-xs text-stone-500">{{ $deliveryNote->quotation?->client?->name }}</p>
                            </td>
                            <td class="px-4 py-3">{{ $deliveryNote->supplier?->name }}</td>
                            <td class="px-4 py-3">{{ $deliveryNote->delivery_date->format('d M Y') }}</td>
                            <td class="px-4 py-3 text-right">{{ $deliveryNote->items_count }}</td>
                            <td class="px-4 py-3 text-right">
                                <div x-data="{ open: false }" x-on:click.outside="open = false" class="relative inline-block text-left">
                                    <button type="button" x-on:click="open = !open" :aria-expanded="open" aria-label="Actions for {{ $deliveryNote->number }}" title="Actions"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-stone-200 bg-white text-stone-600 hover:bg-sand hover:text-ink focus:outline-none focus:ring-2 focus:ring-zenora/30">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                            <circle cx="5" cy="12" r="1.7" />
                                            <circle cx="12" cy="12" r="1.7" />
                                            <circle cx="19" cy="12" r="1.7" />
                                        </svg>
                                    </button>
                                    <div x-cloak x-show="open" x-transition.origin.top.right
                                        class="absolute right-0 z-20 mt-2 w-48 overflow-hidden rounded-lg border border-stone-200 bg-white py-1 text-left shadow-lg">
                                        <a href="{{ route('delivery-notes.pdf', $deliveryNote) }}" target="_blank" x-on:click="open = false"
                                            class="block px-3 py-2 text-sm text-ink hover:bg-sand">Open PDF</a>
                                        <a href="{{ route('delivery-notes.create', ['quotation' => $deliveryNote->quotation_id]) }}" wire:navigate x-on:click="open = false"
                                            class="block px-3 py-2 text-sm text-ink hover:bg-sand">Another delivery</a>
                                        <div class="my-1 border-t border-stone-100"></div>
                                        <button type="button" wire:click="delete({{ $deliveryNote->id }})"
                                            wire:confirm="Delete {{ $deliveryNote->number }}? Its delivered quantities will be restored to the quotation."
                                            class="block w-full px-3 py-2 text-left text-sm text-red-700 hover:bg-red-50">Delete delivery note</button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-stone-500">No delivery notes yet. Create one against a quotation to record delivered materials.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-stone-100 px-4 py-3">{{ $deliveryNotes->links() }}</div>
    </div>
</div>