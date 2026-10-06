<div>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs uppercase tracking-[0.2em] text-stone-500">Sales CRM</p>
            <h1 class="font-display text-3xl">Leads & pipeline</h1>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('leads.reminders') }}" class="btn btn-ghost" wire:navigate>Follow-ups</a>
            <a href="{{ route('leads.create') }}" class="btn btn-primary" wire:navigate>New lead</a>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4 xl:grid-cols-7">
        @foreach ([
            'new' => 'New',
            'contacted' => 'Contacted',
            'qualified' => 'Qualified',
            'quotation' => 'Quotation',
            'negotiation' => 'Negotiation',
            'won' => 'Won',
            'lost' => 'Lost',
        ] as $key => $label)
            <button wire:click="$set('stage', '{{ $stage === $key ? '' : $key }}')" class="card p-3 text-left transition hover:border-zenora {{ $stage === $key ? 'border-zenora ring-2 ring-zenora/20' : '' }}">
                <p class="text-xs uppercase tracking-wide text-stone-500">{{ $label }}</p>
                <p class="mt-1 font-display text-2xl">{{ $pipelineCounts[$key] ?? 0 }}</p>
            </button>
        @endforeach
    </div>

    <div class="mb-4 flex flex-wrap gap-3">
        <input wire:model.live.debounce.300ms="search" class="field max-w-md" placeholder="Search person, company, phone or email">
        <select wire:model.live="stage" class="field max-w-xs">
            <option value="">All pipeline stages</option>
            @foreach (['new', 'contacted', 'qualified', 'quotation', 'negotiation', 'won', 'lost'] as $status)
                <option value="{{ $status }}">{{ ucfirst($status) }}</option>
            @endforeach
        </select>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-sand/60 text-left text-xs uppercase tracking-wide text-stone-500">
                    <tr>
                        <th class="px-4 py-3">Lead</th>
                        <th class="px-4 py-3">Contact</th>
                        <th class="px-4 py-3">Stage</th>
                        <th class="px-4 py-3">Est. value</th>
                        <th class="px-4 py-3">Close date</th>
                        <th class="px-4 py-3">Follow-ups</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($leads as $lead)
                        <tr class="border-t border-stone-100 hover:bg-sand/30">
                            <td class="px-4 py-3">
                                <a href="{{ route('leads.show', $lead) }}" class="font-medium text-zenora hover:underline" wire:navigate>{{ $lead->company ?: $lead->name }}</a>
                                @if ($lead->company && $lead->name !== $lead->company)
                                    <p class="text-xs text-stone-500">{{ $lead->name }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $lead->phone ?: $lead->email ?: '—' }}</td>
                            <td class="px-4 py-3 capitalize">{{ $lead->status }}</td>
                            <td class="px-4 py-3">{{ $lead->estimated_value !== null ? aed($lead->estimated_value) : '—' }}</td>
                            <td class="px-4 py-3">{{ $lead->expected_close_date?->format('d M Y') ?? '—' }}</td>
                            <td class="px-4 py-3">
                                @if ($lead->reminders_count)
                                    <span class="rounded-full bg-copper/10 px-2 py-1 text-xs text-orange-800">{{ $lead->reminders_count }} open</span>
                                @else
                                    <span class="text-stone-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button wire:click="delete({{ $lead->id }})" wire:confirm="Delete this lead from the pipeline?" class="text-red-600 hover:underline">Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-12 text-center text-stone-500">No leads yet. Add a lead to start tracking your sales pipeline.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-stone-100 px-4 py-3">{{ $leads->links() }}</div>
    </div>
</div>
