<?php

namespace App\Livewire\PurchaseOrders;

use App\Models\PurchaseOrder;
use App\Models\Quotation;
use App\Services\PurchaseOrderService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('LPOs — Zenaura')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public ?int $quotation = null;

    #[Url]
    public ?int $open = null;

    public ?int $cancelId = null;

    public string $cancelReason = '';

    public bool $showPicker = false;

    public string $pickerSearch = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function setStatus(string $status): void
    {
        $this->status = in_array($status, ['', PurchaseOrder::STATUS_ISSUED, PurchaseOrder::STATUS_CANCELLED], true) ? $status : '';
        $this->resetPage();
    }

    public function clearQuotation(): void
    {
        $this->quotation = null;
        $this->open = null;
        $this->resetPage();
    }

    public function startCancel(int $id): void
    {
        $order = PurchaseOrder::findOrFail($id);

        if (! $order->isIssued()) {
            return;
        }

        $this->cancelId = $order->id;
        $this->cancelReason = '';
        $this->resetErrorBag('cancelReason');
    }

    public function closeCancel(): void
    {
        $this->reset(['cancelId', 'cancelReason']);
        $this->resetErrorBag('cancelReason');
    }

    public function confirmCancel(PurchaseOrderService $service): void
    {
        if (! $this->cancelId) {
            return;
        }

        $this->validate(['cancelReason' => ['nullable', 'string', 'max:255']], [], ['cancelReason' => 'reason']);

        $order = PurchaseOrder::findOrFail($this->cancelId);

        try {
            $service->cancel($order, $this->cancelReason);
        } catch (\InvalidArgumentException $e) {
            flash_app_toast($this, $e->getMessage(), 'error');
            $this->closeCancel();

            return;
        }

        flash_app_toast($this, 'LPO '.$order->number.' cancelled.');
        $this->closeCancel();
    }

    public function delete(int $id): void
    {
        $order = PurchaseOrder::findOrFail($id);
        $number = $order->number;
        $order->delete();

        if ($this->open === $id) {
            $this->open = null;
        }

        flash_app_toast($this, 'LPO '.$number.' deleted.');
    }

    public function reissue(int $id): void
    {
        $order = PurchaseOrder::findOrFail($id);

        if (! $order->isIssued()) {
            flash_app_toast($this, 'Only an active LPO can be re-issued.', 'error');

            return;
        }

        $this->redirect(route('purchase-orders.create', ['quotation' => $order->quotation_id, 'replace' => $order->id]), navigate: true);
    }

    public function newLpo(): void
    {
        if ($this->quotation && Quotation::whereKey($this->quotation)->exists()) {
            $this->redirect(route('purchase-orders.create', ['quotation' => $this->quotation]), navigate: true);

            return;
        }

        $this->showPicker = true;
        $this->pickerSearch = '';
    }

    public function render()
    {
        $orders = PurchaseOrder::query()
            ->with(['quotation.client', 'supplier', 'replaces', 'replacement'])
            ->when($this->quotation, fn ($q) => $q->where('quotation_id', $this->quotation))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->search, function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('number', 'like', $term)
                        ->orWhere('supplier_quote_number', 'like', $term)
                        ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', $term))
                        ->orWhereHas('quotation', fn ($s) => $s->where('number', 'like', $term));
                });
            })
            ->latest('lpo_date')
            ->latest('id')
            ->paginate(20);

        $pickerResults = $this->showPicker
            ? Quotation::query()
                ->with('client')
                ->withCount(['purchaseOrders' => fn ($q) => $q->issued()])
                ->when(trim($this->pickerSearch) !== '', function ($q) {
                    $term = '%'.trim($this->pickerSearch).'%';
                    $q->where(function ($inner) use ($term) {
                        $inner->where('number', 'like', $term)
                            ->orWhere('reference', 'like', $term)
                            ->orWhereHas('client', fn ($c) => $c->where('name', 'like', $term));
                    });
                })
                ->latest('quote_date')
                ->latest('id')
                ->limit(10)
                ->get()
            : collect();

        $filterQuote = $this->quotation
            ? Quotation::withTrashed()->with('client')->find($this->quotation)
            : null;

        return view('livewire.purchase-orders.index', [
            'orders' => $orders,
            'filterQuote' => $filterQuote,
            'filterSummary' => $filterQuote ? [
                'active' => $filterQuote->purchaseOrders()->issued()->count(),
                'value' => (float) $filterQuote->purchaseOrders()->issued()->sum('grand_total'),
            ] : null,
            'justIssued' => $this->open ? PurchaseOrder::find($this->open) : null,
            'cancelling' => $this->cancelId ? PurchaseOrder::with('supplier')->find($this->cancelId) : null,
            'pickerResults' => $pickerResults,
        ]);
    }
}
