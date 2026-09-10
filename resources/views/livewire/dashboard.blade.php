<div>
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs uppercase tracking-[0.2em] text-stone-500">Overview</p>
            <h1 class="font-display text-3xl text-ink">Good {{ now()->format('A') === 'AM' ? 'morning' : 'afternoon' }}</h1>
        </div>
        <a href="{{ route('quotations.create') }}" class="btn btn-copper" wire:navigate>New quotation</a>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['Products', $productCount, 'In the catalogue'],
            ['Clients', $clientCount, 'With TRN on file'],
            ['Quotes this month', $monthQuotes, now()->format('F Y')],
            ['Drafts', $draftQuotes, 'Waiting to send'],
        ] as [$label, $value, $hint])
            <div class="card p-5">
                <p class="text-xs uppercase tracking-wide text-stone-500">{{ $label }}</p>
                <p class="mt-2 font-display text-3xl">{{ $value }}</p>
                <p class="mt-1 text-xs text-stone-500">{{ $hint }}</p>
            </div>
        @endforeach
    </div>

    <div class="card mt-8 overflow-hidden">
        <div class="flex items-center justify-between border-b border-stone-100 px-5 py-4">
            <h2 class="font-medium">Recent quotations</h2>
            <a href="{{ route('quotations.index') }}" class="text-sm text-zenora hover:underline" wire:navigate>View all</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-sand/60 text-left text-xs uppercase tracking-wide text-stone-500">
                    <tr>
                        <th class="px-5 py-3">Number</th>
                        <th class="px-5 py-3">Client</th>
                        <th class="px-5 py-3">Date</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentQuotes as $quote)
                        <tr class="border-t border-stone-100 hover:bg-sand/40">
                            <td class="px-5 py-3 font-medium">
                                <a href="{{ route('quotations.edit', $quote) }}" class="hover:text-zenora" wire:navigate>{{ $quote->number }}</a>
                            </td>
                            <td class="px-5 py-3">{{ $quote->client?->name }}</td>
                            <td class="px-5 py-3">{{ $quote->quote_date->format('d M Y') }}</td>
                            <td class="px-5 py-3 capitalize">{{ $quote->status }}</td>
                            <td class="px-5 py-3 text-right">{{ aed($quote->grand_total) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-stone-500">No quotations yet. Create your first one.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
