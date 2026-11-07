<div>
    <style>
        @media print {
            aside, header, nav, .no-print { display: none !important; }
            main { padding: 0 !important; }
            body { background: white !important; }
        }
    </style>

    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs uppercase tracking-[0.2em] text-stone-500">Reports</p>
            <h1 class="font-display text-3xl">Sales report</h1>
            <p class="mt-2 max-w-2xl text-sm text-stone-500">Accepted quotations count as sales, on the quotation date. Only the latest revision of each quotation is included. Invoices and receipts are counted on their own dates. Amounts include VAT.</p>
        </div>
        <div class="no-print flex flex-wrap items-end gap-2">
            <div>
                <label class="label" for="report-year">Year</label>
                <select id="report-year" wire:model.live="year" class="field w-36">
                    @foreach ($years as $option)
                        <option value="{{ $option }}">{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <button type="button" class="btn btn-ghost" onclick="window.print()">Print</button>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="card p-5">
            <p class="text-xs uppercase tracking-wide text-stone-500">Quotations</p>
            <p class="mt-2 font-display text-3xl">{{ number_format($summary['quotations']) }}</p>
            <p class="mt-1 text-xs text-stone-500">{{ aed($summary['quoted']) }} quoted in {{ $year }}</p>
        </div>
        <div class="card p-5">
            <p class="text-xs uppercase tracking-wide text-stone-500">Sales</p>
            <p class="mt-2 font-display text-3xl">{{ number_format($summary['sales']) }}</p>
            <p class="mt-1 text-sm font-medium">{{ aed($summary['sales_amount']) }}</p>
            <p class="mt-1 text-xs text-stone-500">
                Average {{ aed($summary['average_sale']) }}
                @if ($bestMonth)
                    · Best month {{ $bestMonth }}
                @endif
            </p>
            <p class="mt-1 text-xs text-stone-500">{{ $year - 1 }}: {{ aed($summary['previous_sales']) }}</p>
        </div>
        <div class="card p-5">
            <p class="text-xs uppercase tracking-wide text-stone-500">Invoiced</p>
            <p class="mt-2 font-display text-3xl">{{ number_format($summary['invoices']) }}</p>
            <p class="mt-1 text-sm font-medium">{{ aed($summary['invoiced']) }}</p>
            <p class="mt-1 text-xs text-stone-500">Proforma invoices dated in {{ $year }}</p>
        </div>
        <div class="card p-5">
            <p class="text-xs uppercase tracking-wide text-stone-500">Collected</p>
            <p class="mt-2 font-display text-3xl">{{ number_format($summary['receipts']) }}</p>
            <p class="mt-1 text-sm font-medium">{{ aed($summary['collected']) }}</p>
            <p class="mt-1 text-xs text-stone-500">
                Outstanding {{ aed($summary['outstanding']) }}
                @if ($summary['outstanding'] < 0)
                    · collected is ahead of invoices this year
                @endif
            </p>
        </div>
    </div>

    <div class="card mt-8 overflow-hidden">
        <div class="border-b border-stone-100 px-5 py-4">
            <h2 class="font-medium">Monthly</h2>
            <p class="mt-1 text-xs text-stone-500">{{ $year }} by quotation, invoice, and receipt date</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-sand/60 text-left text-xs uppercase tracking-wide text-stone-500">
                    <tr>
                        <th class="px-5 py-3">Month</th>
                        <th class="px-5 py-3 text-right">Quotes</th>
                        <th class="px-5 py-3 text-right">Sales</th>
                        <th class="px-5 py-3">Sales amount</th>
                        <th class="px-5 py-3 text-right">Invoiced</th>
                        <th class="px-5 py-3 text-right">Collected</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($months as $month)
                        <tr class="border-t border-stone-100 {{ $year === (int) now()->year && $month['label'] === now()->format('F') ? 'bg-sand/40' : '' }}">
                            <td class="px-5 py-3 font-medium">{{ $month['label'] }}</td>
                            <td class="px-5 py-3 text-right">{{ number_format($month['quotations']) }}</td>
                            <td class="px-5 py-3 text-right">{{ number_format($month['sales']) }}</td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="hidden h-2 w-28 overflow-hidden rounded-full bg-sand sm:block">
                                        <div class="h-full rounded-full bg-zenora" style="width: {{ $month['bar'] }}%"></div>
                                    </div>
                                    <span>{{ aed($month['sales_amount']) }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-right">{{ aed($month['invoiced']) }}</td>
                            <td class="px-5 py-3 text-right">{{ aed($month['collected']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t border-stone-200 bg-sand/40 text-sm font-medium">
                    <tr>
                        <td class="px-5 py-3">Total</td>
                        <td class="px-5 py-3 text-right">{{ number_format($summary['quotations']) }}</td>
                        <td class="px-5 py-3 text-right">{{ number_format($summary['sales']) }}</td>
                        <td class="px-5 py-3">{{ aed($summary['sales_amount']) }}</td>
                        <td class="px-5 py-3 text-right">{{ aed($summary['invoiced']) }}</td>
                        <td class="px-5 py-3 text-right">{{ aed($summary['collected']) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="mt-8 grid gap-8 lg:grid-cols-2">
        <div class="card overflow-hidden">
            <div class="border-b border-stone-100 px-5 py-4">
                <h2 class="font-medium">By status</h2>
                <p class="mt-1 text-xs text-stone-500">Quotations dated in {{ $year }}</p>
            </div>
            <table class="min-w-full text-sm">
                <tbody>
                    @foreach ($statuses as $row)
                        <tr class="border-t border-stone-100">
                            <td class="px-5 py-3 capitalize">{{ $row['status'] }}</td>
                            <td class="px-5 py-3 text-right">{{ number_format($row['count']) }}</td>
                            <td class="px-5 py-3 text-right">{{ aed($row['amount']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="card overflow-hidden">
            <div class="border-b border-stone-100 px-5 py-4">
                <h2 class="font-medium">Top clients</h2>
                <p class="mt-1 text-xs text-stone-500">Accepted sales in {{ $year }}</p>
            </div>
            @if ($clients === [])
                <p class="px-5 py-8 text-center text-sm text-stone-500">No accepted quotations in {{ $year }}.</p>
            @else
                <table class="min-w-full text-sm">
                    <tbody>
                        @foreach ($clients as $client)
                            <tr class="border-t border-stone-100">
                                <td class="px-5 py-3">{{ $client['name'] }}</td>
                                <td class="px-5 py-3 text-right text-stone-500">{{ number_format($client['count']) }}</td>
                                <td class="px-5 py-3 text-right">{{ aed($client['amount']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    @if (collect($salespeople)->contains(fn ($row) => $row['name'] !== 'Unassigned'))
        <div class="card mt-8 overflow-hidden">
            <div class="border-b border-stone-100 px-5 py-4">
                <h2 class="font-medium">By salesperson</h2>
                <p class="mt-1 text-xs text-stone-500">Accepted sales in {{ $year }}</p>
            </div>
            <table class="min-w-full text-sm">
                <tbody>
                    @foreach ($salespeople as $person)
                        <tr class="border-t border-stone-100">
                            <td class="px-5 py-3">{{ $person['name'] }}</td>
                            <td class="px-5 py-3 text-right text-stone-500">{{ number_format($person['count']) }}</td>
                            <td class="px-5 py-3 text-right">{{ aed($person['amount']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
