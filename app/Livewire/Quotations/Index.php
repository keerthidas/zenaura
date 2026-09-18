<?php

namespace App\Livewire\Quotations;

use App\Models\Quotation;
use App\Models\ProformaInvoice;
use App\Services\InvoiceNumberService;
use App\Services\ProformaNumberService;
use App\Services\QuotationNumberService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Quotations — Zenaura')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    public ?int $proformaQuoteId = null;

    public string $proformaPercent = '50';

    public string $proformaDescription = '';

    public string $proformaPaymentTerms = '';

    public string $proformaDeliveryTerms = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
        $this->resetPage();
    }

    public function revise(int $id): void
    {
        $quote = Quotation::findOrFail($id);

        if ($quote->isDraft()) {
            $this->redirect(route('quotations.edit', $quote), navigate: true);

            return;
        }

        $this->redirect(route('quotations.create', ['revise' => $id]), navigate: true);
    }

    public function openProforma(int $id): void
    {
        $quote = Quotation::with('proformas')->findOrFail($id);
        $remaining = $quote->proformaRemainingPercent();

        $this->proformaQuoteId = $id;
        $this->proformaPercent = $remaining > 0
            ? (string) min(50, $remaining)
            : '';
        $this->proformaDescription = ProformaInvoice::DEFAULT_DESCRIPTION;
        $this->proformaPaymentTerms = (string) ($quote->payment_terms ?: 'CASH IN ADVANCE');
        $this->proformaDeliveryTerms = (string) ($quote->delivery_availability ?: $quote->delivery ?: 'TO BE CONFIRMED');
        $this->resetErrorBag(['proformaPercent', 'proformaDescription', 'proformaPaymentTerms', 'proformaDeliveryTerms']);
    }

    public function closeProforma(): void
    {
        $this->reset(['proformaQuoteId', 'proformaPercent', 'proformaDescription', 'proformaPaymentTerms', 'proformaDeliveryTerms']);
        $this->resetErrorBag(['proformaPercent', 'proformaDescription', 'proformaPaymentTerms', 'proformaDeliveryTerms']);
    }

    public function createProforma(ProformaNumberService $numbers)
    {
        if (! $this->proformaQuoteId) {
            return;
        }

        $quote = Quotation::with('proformas')->findOrFail($this->proformaQuoteId);

        $this->validate([
            'proformaPercent' => ['required', 'numeric', 'min:0.01', 'max:100'],
            'proformaDescription' => ['required', 'string', 'max:2000'],
            'proformaPaymentTerms' => ['required', 'string', 'max:150'],
            'proformaDeliveryTerms' => ['required', 'string', 'max:150'],
        ], [], [
            'proformaPercent' => 'percent',
            'proformaDescription' => 'description',
            'proformaPaymentTerms' => 'payment terms',
            'proformaDeliveryTerms' => 'delivery terms',
        ]);

        $percent = round((float) $this->proformaPercent, 2);
        $remaining = $quote->proformaRemainingPercent();

        if ($percent > $remaining) {
            $this->addError('proformaPercent', 'Only '.$remaining.'% remains on this quotation.');

            return;
        }

        $proforma = $numbers->create(
            $quote,
            $percent,
            trim($this->proformaDescription),
            trim($this->proformaPaymentTerms),
            trim($this->proformaDeliveryTerms),
        );

        flash_app_toast($this, 'Proforma invoice '.$proforma->number.' generated.');
        $this->js('window.open('.json_encode(route('quotations.proforma', [$quote, $proforma])).', "_blank")');
        $this->openProforma($quote->id);
    }

    public function updateStatus(int $id, string $status, InvoiceNumberService $invoices): void
    {
        if (! in_array($status, Quotation::STATUSES, true)) {
            return;
        }

        $quote = Quotation::findOrFail($id);
        $payload = ['status' => $status];

        if ($status === 'accepted') {
            $payload = array_merge($payload, $invoices->allocate($quote));
        }

        $quote->update($payload);
        $quote->refresh();
        flash_app_toast($this, 'Status updated.');
    }

    public function duplicate(int $id, QuotationNumberService $numbers): void
    {
        $source = Quotation::with('items')->findOrFail($id);

        $copy = DB::transaction(function () use ($source, $numbers) {
            $quote = $source->replicate([
                'number',
                'base_number',
                'revision',
                'parent_id',
                'sequence_year',
                'sequence_month',
                'sequence_number',
                'invoice_number',
                'invoice_date',
                'invoice_sequence_year',
                'invoice_sequence_month',
                'invoice_sequence_number',
            ]);
            $number = $numbers->preview();
            $quote->fill($numbers->resolveForSave($number));
            $quote->status = 'draft';
            $quote->quote_date = now()->toDateString();
            $quote->parent_id = null;
            $quote->created_by = Auth::id();
            $quote->save();

            foreach ($source->items as $item) {
                $line = $item->replicate();
                $line->quotation_id = $quote->id;
                $line->save();
            }

            return $quote;
        });

        flash_app_toast($this, 'Quotation copied.');

        $this->redirect(route('quotations.edit', $copy), navigate: true);
    }

    public function delete(int $id): void
    {
        Quotation::findOrFail($id)->delete();
        flash_app_toast($this, 'Quotation deleted.');
    }

    public function render()
    {
        $quotations = Quotation::query()
            ->with(['client', 'proformas'])
            ->when($this->search, function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('number', 'like', $term)
                        ->orWhere('reference', 'like', $term)
                        ->orWhereHas('client', fn ($c) => $c->where('name', 'like', $term)->orWhere('trn', 'like', $term));
                });
            })
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->latest('quote_date')
            ->latest('id')
            ->paginate(15);

        return view('livewire.quotations.index', [
            'quotations' => $quotations,
            'proformaQuote' => $this->proformaQuoteId
                ? Quotation::with(['client', 'proformas'])->find($this->proformaQuoteId)
                : null,
        ]);
    }
}
