<?php

namespace App\Livewire\Quotations;

use App\Models\Quotation;
use App\Services\InvoiceNumberService;
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
        $this->redirect(route('quotations.create', ['revise' => $id]), navigate: true);
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
        flash_app_toast($this, [
            'eyebrow' => 'Status changed',
            'title' => $quote->number,
            'body' => 'Now marked '.$status.($quote->invoice_number ? ' · Invoice '.$quote->invoice_number : ''),
        ]);
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

        flash_app_toast($this, [
            'eyebrow' => 'Quotation duplicated',
            'title' => $copy->number,
            'body' => 'A copy is ready to edit.',
        ]);

        $this->redirect(route('quotations.edit', $copy), navigate: true);
    }

    public function delete(int $id): void
    {
        Quotation::findOrFail($id)->delete();
        flash_app_toast($this, [
            'eyebrow' => 'Quotation archived',
            'title' => 'Removed from the live list',
            'body' => 'You can keep working on the remaining quotations.',
        ]);
    }

    public function render()
    {
        $quotations = Quotation::query()
            ->with('client')
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

        return view('livewire.quotations.index', compact('quotations'));
    }
}
