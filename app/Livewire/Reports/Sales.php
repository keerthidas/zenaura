<?php

namespace App\Livewire\Reports;

use App\Models\Client;
use App\Models\ProformaInvoice;
use App\Models\Quotation;
use App\Models\ReceiptVoucher;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Sales report — Zenaura')]
class Sales extends Component
{
    #[Url]
    public int $year = 0;

    public function mount(): void
    {
        $this->year = $this->validYear($this->year);
    }

    public function updatedYear(mixed $year): void
    {
        $this->year = $this->validYear($year);
    }

    public function render()
    {
        $year = $this->validYear($this->year);
        $report = $this->report($year);

        return view('livewire.reports.sales', [
            'year' => $year,
            'years' => $this->years($year),
            'summary' => $report['summary'],
            'months' => $report['months'],
            'statuses' => $report['statuses'],
            'clients' => $report['clients'],
            'salespeople' => $report['salespeople'],
            'bestMonth' => $report['bestMonth'],
        ]);
    }

    /**
     * @return array{
     *     summary: array<string, int|float>,
     *     months: list<array<string, int|float|string>>,
     *     statuses: list<array{status: string, count: int, amount: float}>,
     *     clients: list<array{name: string, count: int, amount: float}>,
     *     salespeople: list<array{name: string, count: int, amount: float}>,
     *     bestMonth: ?string
     * }
     */
    protected function report(int $year): array
    {
        $quotes = Quotation::query()
            ->latestRevision()
            ->whereYear('quote_date', $year)
            ->get(['id', 'client_id', 'salesperson', 'status', 'quote_date', 'grand_total']);

        $proformas = ProformaInvoice::query()
            ->whereYear('invoice_date', $year)
            ->whereIn('quotation_id', Quotation::withTrashed()->latestRevision()->select('quotations.id'))
            ->get(['invoice_date', 'grand_total']);

        $receipts = ReceiptVoucher::query()
            ->whereYear('received_date', $year)
            ->whereHas('proforma', fn ($query) => $query->whereIn('quotation_id', Quotation::withTrashed()->latestRevision()->select('quotations.id')))
            ->get(['received_date', 'amount']);

        $months = [];
        for ($month = 1; $month <= 12; $month++) {
            $date = Carbon::create($year, $month, 1);
            $months[$month] = [
                'label' => $date->format('F'),
                'quotations' => 0,
                'sales' => 0,
                'quoted' => 0.0,
                'sales_amount' => 0.0,
                'invoices' => 0,
                'invoiced' => 0.0,
                'receipts' => 0,
                'collected' => 0.0,
                'bar' => 0.0,
            ];
        }

        foreach ($quotes as $quote) {
            $month = (int) $quote->quote_date->month;
            $amount = (float) $quote->grand_total;
            $months[$month]['quotations']++;
            $months[$month]['quoted'] += $amount;
            if ($quote->status === 'accepted') {
                $months[$month]['sales']++;
                $months[$month]['sales_amount'] += $amount;
            }
        }

        foreach ($proformas as $proforma) {
            $month = (int) $proforma->invoice_date->month;
            $months[$month]['invoices']++;
            $months[$month]['invoiced'] += (float) $proforma->grand_total;
        }

        foreach ($receipts as $receipt) {
            $month = (int) $receipt->received_date->month;
            $months[$month]['receipts']++;
            $months[$month]['collected'] += (float) $receipt->amount;
        }

        $peak = 0.0;
        foreach ($months as $row) {
            $peak = max($peak, $row['sales_amount']);
        }

        foreach ($months as $month => $row) {
            $months[$month]['quoted'] = round($row['quoted'], 2);
            $months[$month]['sales_amount'] = round($row['sales_amount'], 2);
            $months[$month]['invoiced'] = round($row['invoiced'], 2);
            $months[$month]['collected'] = round($row['collected'], 2);
            $months[$month]['bar'] = $peak > 0 ? round($row['sales_amount'] / $peak * 100, 1) : 0.0;
        }

        $accepted = $quotes->where('status', 'accepted');
        $salesAmount = round((float) $accepted->sum('grand_total'), 2);
        $invoiced = round((float) $proformas->sum('grand_total'), 2);
        $collected = round((float) $receipts->sum('amount'), 2);
        $previousSales = round((float) Quotation::query()
            ->latestRevision()
            ->whereYear('quote_date', $year - 1)
            ->where('status', 'accepted')
            ->sum('grand_total'), 2);

        $statuses = [];
        foreach (Quotation::STATUSES as $status) {
            $subset = $quotes->where('status', $status);
            $statuses[] = [
                'status' => $status,
                'count' => $subset->count(),
                'amount' => round((float) $subset->sum('grand_total'), 2),
            ];
        }

        $names = Client::withTrashed()
            ->whereIn('id', $accepted->pluck('client_id')->unique()->filter())
            ->pluck('name', 'id');

        $clients = $accepted
            ->groupBy('client_id')
            ->map(function ($group, $clientId) use ($names) {
                return [
                    'name' => $names[$clientId] ?? 'Unknown client',
                    'count' => $group->count(),
                    'amount' => round((float) $group->sum('grand_total'), 2),
                ];
            })
            ->sortByDesc('amount')
            ->take(8)
            ->values()
            ->all();

        $salespeople = $accepted
            ->groupBy(fn (Quotation $quote) => trim((string) $quote->salesperson) ?: 'Unassigned')
            ->map(fn ($group, $name) => [
                'name' => $name,
                'count' => $group->count(),
                'amount' => round((float) $group->sum('grand_total'), 2),
            ])
            ->sortByDesc('amount')
            ->values()
            ->all();

        $best = collect($months)->sortByDesc('sales_amount')->first();

        return [
            'summary' => [
                'quotations' => $quotes->count(),
                'quoted' => round((float) $quotes->sum('grand_total'), 2),
                'sales' => $accepted->count(),
                'sales_amount' => $salesAmount,
                'average_sale' => $accepted->count() > 0 ? round($salesAmount / $accepted->count(), 2) : 0.0,
                'previous_sales' => $previousSales,
                'invoices' => $proformas->count(),
                'invoiced' => $invoiced,
                'receipts' => $receipts->count(),
                'collected' => $collected,
                'outstanding' => round($invoiced - $collected, 2),
            ],
            'months' => array_values($months),
            'statuses' => $statuses,
            'clients' => $clients,
            'salespeople' => $salespeople,
            'bestMonth' => ($best['sales_amount'] ?? 0) > 0 ? $best['label'] : null,
        ];
    }

    /**
     * @return list<int>
     */
    protected function years(int $selected): array
    {
        $years = collect([(int) now()->year, $selected]);

        foreach ([
            Quotation::query()->latestRevision()->pluck('quote_date'),
            ProformaInvoice::query()
                ->whereIn('quotation_id', Quotation::withTrashed()->latestRevision()->select('quotations.id'))
                ->pluck('invoice_date'),
            ReceiptVoucher::query()
                ->whereHas('proforma', fn ($query) => $query->whereIn('quotation_id', Quotation::withTrashed()->latestRevision()->select('quotations.id')))
                ->pluck('received_date'),
        ] as $dates) {
            $years = $years->merge(
                $dates->filter()->map(fn ($date) => (int) Carbon::parse($date)->year)
            );
        }

        return $years->unique()->sortDesc()->values()->all();
    }

    protected function validYear(mixed $year): int
    {
        $year = (int) $year;

        if ($year < 2000 || $year > 2100) {
            return (int) now()->year;
        }

        return $year;
    }
}
