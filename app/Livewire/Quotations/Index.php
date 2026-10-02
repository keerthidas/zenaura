<?php

namespace App\Livewire\Quotations;

use App\Models\ProformaInvoice;
use App\Models\Quotation;
use App\Models\ReceiptVoucher;
use App\Services\InvoiceNumberService;
use App\Services\ProformaNumberService;
use App\Services\QuotationNumberService;
use App\Services\ReceiptNumberService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
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

    public ?int $receiptProformaId = null;

    public string $receiptDate = '';

    public string $receiptPaymentMode = 'Bank Transfer';

    public string $receiptReference = '';

    public bool $showProformaForm = false;

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
        $quote = Quotation::with('proformas.receipt')->findOrFail($id);
        $remaining = $quote->proformaRemainingPercent();

        $this->proformaQuoteId = $id;
        $this->proformaPercent = $remaining > 0
            ? (string) min(50, $remaining)
            : '';
        $this->proformaDescription = ProformaInvoice::DEFAULT_DESCRIPTION;
        $this->proformaPaymentTerms = (string) ($quote->payment_terms ?: 'CASH IN ADVANCE');
        $this->proformaDeliveryTerms = (string) ($quote->delivery_availability ?: $quote->delivery ?: 'TO BE CONFIRMED');
        $this->showProformaForm = $quote->proformas->isEmpty() && $remaining > 0;
        $this->reset(['receiptProformaId', 'receiptDate', 'receiptPaymentMode', 'receiptReference']);
        $this->resetErrorBag(['proformaPercent', 'proformaDescription', 'proformaPaymentTerms', 'proformaDeliveryTerms', 'receiptDate', 'receiptPaymentMode', 'receiptReference']);
    }

    public function closeProforma(): void
    {
        $this->reset([
            'proformaQuoteId',
            'proformaPercent',
            'proformaDescription',
            'proformaPaymentTerms',
            'proformaDeliveryTerms',
            'receiptProformaId',
            'receiptDate',
            'receiptPaymentMode',
            'receiptReference',
            'showProformaForm',
        ]);
        $this->resetErrorBag(['proformaPercent', 'proformaDescription', 'proformaPaymentTerms', 'proformaDeliveryTerms', 'receiptDate', 'receiptPaymentMode', 'receiptReference']);
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
        $this->showProformaForm = false;
        $this->openProforma($quote->id);
    }

    public function startNewProforma(): void
    {
        if (! $this->proformaQuoteId) {
            return;
        }

        $this->closeReceipt();
        $quote = Quotation::with('proformas')->findOrFail($this->proformaQuoteId);
        $remaining = $quote->proformaRemainingPercent();
        $this->proformaPercent = $remaining > 0 ? (string) min(50, $remaining) : '';
        $this->showProformaForm = true;
    }

    public function cancelNewProforma(): void
    {
        $this->showProformaForm = false;
        $this->resetErrorBag(['proformaPercent', 'proformaDescription', 'proformaPaymentTerms', 'proformaDeliveryTerms']);
    }

    public function deleteProforma(int $id): void
    {
        if (! $this->proformaQuoteId) {
            return;
        }

        $proforma = ProformaInvoice::query()
            ->where('quotation_id', $this->proformaQuoteId)
            ->findOrFail($id);

        $number = $proforma->number;
        $proforma->delete();
        flash_app_toast($this, 'Proforma invoice '.$number.' deleted.');
        $this->closeReceipt();
        $this->openProforma($this->proformaQuoteId);
    }

    public function openReceipt(int $id): void
    {
        if (! $this->proformaQuoteId) {
            return;
        }

        $proforma = ProformaInvoice::query()
            ->with('receipt')
            ->where('quotation_id', $this->proformaQuoteId)
            ->findOrFail($id);

        if ($proforma->receipt) {
            $this->js('window.open('.json_encode(route('quotations.receipt', [$proforma->quotation_id, $proforma, $proforma->receipt])).', "_blank")');

            return;
        }

        $this->receiptProformaId = $proforma->id;
        $this->receiptDate = now()->toDateString();
        $this->receiptPaymentMode = 'Bank Transfer';
        $this->receiptReference = '';
        $this->showProformaForm = false;
        $this->resetErrorBag(['receiptDate', 'receiptPaymentMode', 'receiptReference']);
    }

    public function closeReceipt(): void
    {
        $this->reset(['receiptProformaId', 'receiptDate', 'receiptPaymentMode', 'receiptReference']);
        $this->resetErrorBag(['receiptDate', 'receiptPaymentMode', 'receiptReference']);
    }

    public function createReceipt(ReceiptNumberService $numbers)
    {
        if (! $this->proformaQuoteId || ! $this->receiptProformaId) {
            return;
        }

        $proforma = ProformaInvoice::query()
            ->with('receipt')
            ->where('quotation_id', $this->proformaQuoteId)
            ->findOrFail($this->receiptProformaId);

        $this->validate([
            'receiptDate' => ['required', 'date'],
            'receiptPaymentMode' => ['required', 'string', Rule::in(ReceiptVoucher::PAYMENT_MODES)],
            'receiptReference' => ['nullable', 'string', 'max:120'],
        ], [], [
            'receiptDate' => 'received date',
            'receiptPaymentMode' => 'payment mode',
            'receiptReference' => 'reference',
        ]);

        $receipt = $numbers->create(
            $proforma,
            $this->receiptDate,
            $this->receiptPaymentMode,
            trim($this->receiptReference) ?: null,
        );

        flash_app_toast($this, 'Receipt voucher '.$receipt->number.' generated.');
        $this->js('window.open('.json_encode(route('quotations.receipt', [$this->proformaQuoteId, $proforma, $receipt])).', "_blank")');
        $this->closeReceipt();
        $this->openProforma($this->proformaQuoteId);
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
            ->with(['client', 'proformas.receipt'])
            ->withCount(['purchaseOrders as active_purchase_orders_count' => fn ($q) => $q->issued()])
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
                ? Quotation::with(['client', 'proformas.receipt'])->find($this->proformaQuoteId)
                : null,
        ]);
    }
}
