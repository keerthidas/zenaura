<?php

namespace App\Livewire;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\ProformaInvoice;
use App\Models\Quotation;
use App\Models\ReceiptVoucher;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Dashboard — Zenaura')]
class Dashboard extends Component
{
    public function render()
    {
        $now = now();
        $monthStart = $now->copy()->startOfMonth();
        $yearStart = $now->copy()->startOfYear();
        $trendStart = $monthStart->copy()->subMonths(6)->startOfMonth();
        $fetchFrom = $trendStart->lt($yearStart) ? $trendStart->copy() : $yearStart->copy();

        $quotes = Quotation::latestRevision()
            ->whereDate('quote_date', '>=', $fetchFrom->toDateString())
            ->get(['id', 'salesperson', 'status', 'quote_date', 'grand_total']);

        $proformas = ProformaInvoice::query()
            ->whereDate('invoice_date', '>=', $fetchFrom->toDateString())
            ->whereIn('quotation_id', Quotation::withTrashed()->latestRevision()->select('quotations.id'))
            ->get(['invoice_date', 'grand_total']);

        $receipts = ReceiptVoucher::query()
            ->whereDate('received_date', '>=', $fetchFrom->toDateString())
            ->whereHas('proforma', fn ($query) => $query->whereIn('quotation_id', Quotation::withTrashed()->latestRevision()->select('quotations.id')))
            ->get(['received_date', 'amount']);

        $months = $this->months($fetchFrom, $monthStart, $quotes, $proformas, $receipts);
        $currentKey = $monthStart->format('Y-m');
        $previousKey = $monthStart->copy()->subMonth()->format('Y-m');
        $current = $months[$currentKey];
        $previous = $months[$previousKey] ?? $this->emptyMonth($monthStart->copy()->subMonth());

        $yearKey = $now->year;
        $ytd = collect($months)->filter(fn (array $month) => $month['year'] === $yearKey);

        $openQuotes = Quotation::latestRevision()->where('status', 'sent');

        return view('livewire.dashboard', [
            'greeting' => $now->hour < 12 ? 'morning' : ($now->hour < 17 ? 'afternoon' : 'evening'),
            'monthLabel' => $monthStart->format('F Y'),
            'previousMonthLabel' => $monthStart->copy()->subMonth()->format('F'),
            'current' => $current,
            'confirmedChange' => $this->change($current['confirmed_amount'], $previous['confirmed_amount']),
            'quotedChange' => $this->change($current['quotes_amount'], $previous['quotes_amount']),
            'invoicedChange' => $this->change($current['invoiced'], $previous['invoiced']),
            'collectedChange' => $this->change($current['collected'], $previous['collected']),
            'conversion' => $current['quotes_count'] > 0
                ? (int) round($current['confirmed_count'] / $current['quotes_count'] * 100)
                : null,
            'ytdConfirmed' => round((float) $ytd->sum('confirmed_amount'), 2),
            'ytdQuoted' => round((float) $ytd->sum('quotes_amount'), 2),
            'outstanding' => round((float) $ytd->sum('invoiced') - (float) $ytd->sum('collected'), 2),
            'openQuoteCount' => (clone $openQuotes)->count(),
            'openQuoteValue' => round((float) (clone $openQuotes)->sum('grand_total'), 2),
            'draftCount' => Quotation::latestRevision()->where('status', 'draft')->count(),
            'trend' => $this->trend(collect($months), $currentKey),
            'salespeople' => $this->salespeople($quotes, $currentKey),
            'recentConfirmed' => Quotation::latestRevision()
                ->with('client')
                ->where('status', 'accepted')
                ->whereDate('quote_date', '>=', $monthStart->toDateString())
                ->orderByDesc('quote_date')
                ->orderByDesc('id')
                ->limit(6)
                ->get(),
            'recentQuotes' => Quotation::latestRevision()->with('client')->latest('quote_date')->latest('id')->limit(8)->get(),
            'leadCount' => Lead::whereNotIn('status', ['won', 'lost'])->count(),
            'pipelineValue' => Lead::whereNotIn('status', ['won', 'lost'])->sum('estimated_value'),
            'openReminderCount' => LeadActivity::whereHas('lead')
                ->whereNotNull('reminder_at')
                ->whereNull('completed_at')
                ->count(),
            'upcomingReminders' => LeadActivity::with('lead')
                ->whereHas('lead')
                ->whereNotNull('reminder_at')
                ->whereNull('completed_at')
                ->where('reminder_at', '<=', $now->copy()->addDays(7))
                ->orderBy('reminder_at')
                ->limit(8)
                ->get(),
        ]);
    }

    /**
     * @param  Collection<int, Quotation>  $quotes
     * @param  Collection<int, ProformaInvoice>  $proformas
     * @param  Collection<int, ReceiptVoucher>  $receipts
     * @return array<string, array<string, mixed>>
     */
    protected function months(Carbon $from, Carbon $through, Collection $quotes, Collection $proformas, Collection $receipts): array
    {
        $months = [];
        $cursor = $from->copy()->startOfMonth();

        while ($cursor->lte($through)) {
            $months[$cursor->format('Y-m')] = $this->emptyMonth($cursor);
            $cursor->addMonth();
        }

        foreach ($quotes as $quote) {
            $key = $quote->quote_date->format('Y-m');
            if (! isset($months[$key]) || $quote->status === 'draft') {
                continue;
            }

            $amount = (float) $quote->grand_total;
            $months[$key]['quotes_count']++;
            $months[$key]['quotes_amount'] += $amount;

            if ($quote->status === 'accepted') {
                $months[$key]['confirmed_count']++;
                $months[$key]['confirmed_amount'] += $amount;
            }
        }

        foreach ($proformas as $proforma) {
            $key = $proforma->invoice_date->format('Y-m');
            if (isset($months[$key])) {
                $months[$key]['invoice_count']++;
                $months[$key]['invoiced'] += (float) $proforma->grand_total;
            }
        }

        foreach ($receipts as $receipt) {
            $key = $receipt->received_date->format('Y-m');
            if (isset($months[$key])) {
                $months[$key]['receipt_count']++;
                $months[$key]['collected'] += (float) $receipt->amount;
            }
        }

        foreach ($months as $key => $month) {
            $months[$key]['quotes_amount'] = round($month['quotes_amount'], 2);
            $months[$key]['confirmed_amount'] = round($month['confirmed_amount'], 2);
            $months[$key]['invoiced'] = round($month['invoiced'], 2);
            $months[$key]['collected'] = round($month['collected'], 2);
        }

        return $months;
    }

    /**
     * @return array<string, mixed>
     */
    protected function emptyMonth(Carbon $date): array
    {
        return [
            'key' => $date->format('Y-m'),
            'label' => $date->format('M Y'),
            'year' => (int) $date->year,
            'quotes_count' => 0,
            'quotes_amount' => 0.0,
            'confirmed_count' => 0,
            'confirmed_amount' => 0.0,
            'invoice_count' => 0,
            'invoiced' => 0.0,
            'receipt_count' => 0,
            'collected' => 0.0,
        ];
    }

    /**
     * @param  Collection<string, array<string, mixed>>  $months
     * @return list<array<string, mixed>>
     */
    protected function trend(Collection $months, string $currentKey): array
    {
        $ordered = $months->sortKeys();
        $rows = [];
        $previousConfirmed = null;
        $previousQuoted = null;

        foreach ($ordered as $month) {
            $row = $month;
            $row['current'] = $month['key'] === $currentKey;
            $row['confirmed_change'] = $previousConfirmed === null
                ? null
                : $this->change($month['confirmed_amount'], $previousConfirmed);
            $row['quoted_change'] = $previousQuoted === null
                ? null
                : $this->change($month['quotes_amount'], $previousQuoted);
            $previousConfirmed = $month['confirmed_amount'];
            $previousQuoted = $month['quotes_amount'];
            $rows[] = $row;
        }

        $visible = array_slice($rows, -6);
        $peak = max(1, (float) collect($visible)->max('confirmed_amount'));

        foreach ($visible as $index => $month) {
            $visible[$index]['bar'] = round($month['confirmed_amount'] / $peak * 100, 1);
        }

        return $visible;
    }

    /**
     * @param  Collection<int, Quotation>  $quotes
     * @return list<array{name: string, count: int, amount: float}>
     */
    protected function salespeople(Collection $quotes, string $monthKey): array
    {
        return $quotes
            ->filter(fn (Quotation $quote) => $quote->status === 'accepted' && $quote->quote_date->format('Y-m') === $monthKey)
            ->groupBy(fn (Quotation $quote) => trim((string) $quote->salesperson) ?: 'Unassigned')
            ->map(fn (Collection $group, string $name) => [
                'name' => $name,
                'count' => $group->count(),
                'amount' => round((float) $group->sum('grand_total'), 2),
            ])
            ->sortByDesc('amount')
            ->values()
            ->all();
    }

    /**
     * @return array{direction: string, text: string}
     */
    protected function change(float $current, float $previous): array
    {
        if ($previous == 0.0 && $current == 0.0) {
            return ['direction' => 'flat', 'text' => 'No change'];
        }

        if ($previous == 0.0) {
            return ['direction' => 'up', 'text' => 'New'];
        }

        $percent = (($current - $previous) / abs($previous)) * 100;

        if (abs($percent) < 0.5) {
            return ['direction' => 'flat', 'text' => 'No change'];
        }

        return [
            'direction' => $percent > 0 ? 'up' : 'down',
            'text' => ($percent > 0 ? '+' : '').number_format($percent, 0).'%',
        ];
    }
}
