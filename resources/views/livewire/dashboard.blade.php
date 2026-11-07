<div>
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs uppercase tracking-[0.2em] text-stone-500">Overview · {{ $monthLabel }}</p>
            <h1 class="font-display text-3xl text-ink">Good {{ $greeting }}</h1>
            <p class="mt-2 max-w-2xl text-sm text-stone-500">Confirmed orders are accepted quotations, dated in the month they were quoted. Amounts include VAT and are compared with {{ $previousMonthLabel }}.</p>
        </div>
        <a href="{{ route('quotations.create') }}" class="btn btn-copper" wire:navigate>New quotation</a>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <a href="{{ route('quotations.index', ['status' => 'accepted']) }}" class="card relative overflow-hidden p-5 transition hover:border-copper" wire:navigate>
            <span class="absolute inset-x-0 top-0 h-1 bg-copper"></span>
            <p class="text-xs uppercase tracking-wide text-stone-500">Confirmed orders this month</p>
            <p class="mt-3 font-display text-3xl leading-none tracking-tight">{{ aed($current['confirmed_amount']) }}</p>
            <p class="mt-2 text-sm">{{ number_format($current['confirmed_count']) }} {{ $current['confirmed_count'] === 1 ? 'order' : 'orders' }}</p>
            @include('livewire.partials.dashboard-change', ['change' => $confirmedChange, 'against' => $previousMonthLabel])
            <p class="mt-2 text-xs text-stone-500">
                Year to date {{ aed($ytdConfirmed) }}
                @if ($conversion !== null)
                    · {{ $conversion }}% of this month's quotes
                @endif
            </p>
        </a>

        <a href="{{ route('quotations.index') }}" class="card relative overflow-hidden p-5 transition hover:border-zenora" wire:navigate>
            <span class="absolute inset-x-0 top-0 h-1 bg-zenora"></span>
            <p class="text-xs uppercase tracking-wide text-stone-500">Quotations this month</p>
            <p class="mt-3 font-display text-3xl leading-none tracking-tight">{{ aed($current['quotes_amount']) }}</p>
            <p class="mt-2 text-sm">{{ number_format($current['quotes_count']) }} {{ $current['quotes_count'] === 1 ? 'quotation' : 'quotations' }}</p>
            @include('livewire.partials.dashboard-change', ['change' => $quotedChange, 'against' => $previousMonthLabel])
            <p class="mt-2 text-xs text-stone-500">Year to date {{ aed($ytdQuoted) }} · {{ number_format($draftCount) }} {{ $draftCount === 1 ? 'draft' : 'drafts' }} waiting</p>
        </a>

        <a href="{{ route('reports.sales') }}" class="card relative overflow-hidden p-5 transition hover:border-amber-600" wire:navigate>
            <span class="absolute inset-x-0 top-0 h-1 bg-amber-600"></span>
            <p class="text-xs uppercase tracking-wide text-stone-500">Invoiced this month</p>
            <p class="mt-3 font-display text-3xl leading-none tracking-tight">{{ aed($current['invoiced']) }}</p>
            <p class="mt-2 text-sm">{{ number_format($current['invoice_count']) }} {{ $current['invoice_count'] === 1 ? 'proforma' : 'proformas' }}</p>
            @include('livewire.partials.dashboard-change', ['change' => $invoicedChange, 'against' => $previousMonthLabel])
            <p class="mt-2 text-xs text-stone-500">Proforma invoices dated this month</p>
        </a>

        <a href="{{ route('reports.sales') }}" class="card relative overflow-hidden p-5 transition hover:border-emerald-700" wire:navigate>
            <span class="absolute inset-x-0 top-0 h-1 bg-emerald-600"></span>
            <p class="text-xs uppercase tracking-wide text-stone-500">Collected this month</p>
            <p class="mt-3 font-display text-3xl leading-none tracking-tight">{{ aed($current['collected']) }}</p>
            <p class="mt-2 text-sm">{{ number_format($current['receipt_count']) }} {{ $current['receipt_count'] === 1 ? 'receipt' : 'receipts' }}</p>
            @include('livewire.partials.dashboard-change', ['change' => $collectedChange, 'against' => $previousMonthLabel])
            <p class="mt-2 text-xs text-stone-500">Receipts dated this month</p>
        </a>
    </div>

    <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <a href="{{ route('quotations.index', ['status' => 'sent']) }}" class="card p-5 transition hover:border-zenora" wire:navigate>
            <p class="text-xs uppercase tracking-wide text-stone-500">Open quotations</p>
            <p class="mt-2 font-display text-2xl">{{ aed($openQuoteValue) }}</p>
            <p class="mt-1 text-xs text-stone-500">{{ number_format($openQuoteCount) }} sent, waiting on a decision</p>
        </a>
        <a href="{{ route('reports.sales') }}" class="card p-5 transition hover:border-zenora" wire:navigate>
            <p class="text-xs uppercase tracking-wide text-stone-500">Outstanding</p>
            <p class="mt-2 font-display text-2xl">{{ aed($outstanding) }}</p>
            <p class="mt-1 text-xs text-stone-500">Invoiced minus collected, this year</p>
        </a>
        <a href="{{ route('leads.index') }}" class="card p-5 transition hover:border-zenora" wire:navigate>
            <p class="text-xs uppercase tracking-wide text-stone-500">Active leads</p>
            <p class="mt-2 font-display text-2xl">{{ number_format($leadCount) }}</p>
            <p class="mt-1 text-xs text-stone-500">Pipeline {{ aed($pipelineValue) }}</p>
        </a>
        <a href="{{ route('leads.reminders') }}" class="card p-5 transition hover:border-zenora" wire:navigate>
            <p class="text-xs uppercase tracking-wide text-stone-500">Open follow-ups</p>
            <p class="mt-2 font-display text-2xl">{{ number_format($openReminderCount) }}</p>
            <p class="mt-1 text-xs text-stone-500">Due or scheduled</p>
        </a>
    </div>

    <div class="mt-8 grid gap-8 xl:grid-cols-5">
        <div class="card overflow-hidden xl:col-span-3">
            <div class="flex items-center justify-between border-b border-stone-100 px-5 py-4">
                <div>
                    <h2 class="font-medium">Last six months</h2>
                    <p class="mt-1 text-xs text-stone-500">Confirmed orders and quotations, against the month before</p>
                </div>
                <a href="{{ route('reports.sales') }}" class="text-sm text-zenora hover:underline" wire:navigate>Full report</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-sand/60 text-left text-xs uppercase tracking-wide text-stone-500">
                        <tr>
                            <th class="px-5 py-3">Month</th>
                            <th class="px-5 py-3">Confirmed</th>
                            <th class="px-5 py-3 text-right">Quotations</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (array_reverse($trend) as $month)
                            <tr class="border-t border-stone-100 {{ $month['current'] ? 'bg-sand/50' : '' }}">
                                <td class="px-5 py-3 font-medium">
                                    {{ $month['label'] }}
                                    @if ($month['current'])
                                        <span class="ml-1 text-xs font-normal text-stone-500">This month</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="hidden h-1.5 w-16 overflow-hidden rounded-full bg-sand sm:block">
                                            <div class="h-full rounded-full bg-copper" style="width: {{ $month['bar'] }}%"></div>
                                        </div>
                                        <span>{{ aed($month['confirmed_amount']) }}</span>
                                        <span class="text-xs text-stone-500">{{ number_format($month['confirmed_count']) }}</span>
                                        @if ($month['confirmed_change'] && $month['confirmed_change']['direction'] !== 'flat')
                                            @include('livewire.partials.dashboard-change', ['change' => $month['confirmed_change'], 'compact' => true])
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <span>{{ aed($month['quotes_amount']) }}</span>
                                    <span class="ml-2 text-xs text-stone-500">{{ number_format($month['quotes_count']) }}</span>
                                    @if ($month['quoted_change'] && $month['quoted_change']['direction'] !== 'flat')
                                        <span class="ml-2">@include('livewire.partials.dashboard-change', ['change' => $month['quoted_change'], 'compact' => true])</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card overflow-hidden xl:col-span-2">
            <div class="flex items-center justify-between border-b border-stone-100 px-5 py-4">
                <h2 class="font-medium">Confirmed this month</h2>
                <a href="{{ route('quotations.index', ['status' => 'accepted']) }}" class="text-sm text-zenora hover:underline" wire:navigate>All accepted</a>
            </div>
            <div class="divide-y divide-stone-100">
                @forelse ($recentConfirmed as $quote)
                    <a href="{{ route('quotations.create', ['revise' => $quote->id]) }}" class="flex items-start justify-between gap-3 px-5 py-3 hover:bg-sand/30" wire:navigate>
                        <span>
                            <span class="block text-sm font-medium">{{ $quote->client?->name ?: 'No client' }}</span>
                            <span class="text-xs text-stone-500">{{ $quote->number }} · {{ $quote->quote_date->format('d M') }}{{ $quote->salesperson ? ' · '.$quote->salesperson : '' }}</span>
                        </span>
                        <span class="shrink-0 text-sm">{{ aed($quote->grand_total) }}</span>
                    </a>
                @empty
                    <p class="px-5 py-8 text-center text-sm text-stone-500">No confirmed orders in {{ $monthLabel }}.</p>
                @endforelse
            </div>
            @if ($salespeople !== [])
                <div class="border-t border-stone-100 bg-sand/30 px-5 py-4">
                    <p class="text-xs uppercase tracking-wide text-stone-500">By salesperson</p>
                    <ul class="mt-2 space-y-1.5 text-sm">
                        @foreach ($salespeople as $person)
                            <li class="flex items-center justify-between gap-3">
                                <span>{{ $person['name'] }} <span class="text-xs text-stone-500">{{ number_format($person['count']) }}</span></span>
                                <span>{{ aed($person['amount']) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
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
                                <a href="{{ $quote->isDraft() ? route('quotations.edit', $quote) : route('quotations.create', ['revise' => $quote->id]) }}" class="hover:text-zenora" wire:navigate>{{ $quote->number }}</a>
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

    <div class="card mt-8 overflow-hidden">
        <div class="flex items-center justify-between border-b border-stone-100 px-5 py-4">
            <h2 class="font-medium">Follow-ups due soon</h2>
            <a href="{{ route('leads.reminders') }}" class="text-sm text-zenora hover:underline" wire:navigate>All reminders</a>
        </div>
        <div class="divide-y divide-stone-100">
            @forelse ($upcomingReminders as $reminder)
                <a href="{{ route('leads.show', $reminder->lead) }}" class="flex flex-wrap items-center justify-between gap-2 px-5 py-3 hover:bg-sand/30" wire:navigate>
                    <span>
                        <span class="block text-sm font-medium">{{ $reminder->lead->company ?: $reminder->lead->name }}</span>
                        <span class="text-xs text-stone-500">{{ $reminder->subject }}</span>
                    </span>
                    <span class="text-xs {{ $reminder->isOverdue() ? 'font-medium text-red-700' : 'text-stone-500' }}">
                        {{ $reminder->isOverdue() ? 'Overdue · ' : '' }}{{ $reminder->reminder_at->format('d M, H:i') }}
                    </span>
                </a>
            @empty
                <p class="px-5 py-8 text-center text-sm text-stone-500">No follow-ups due in the next 7 days.</p>
            @endforelse
        </div>
    </div>
</div>
