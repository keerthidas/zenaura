<?php

namespace App\Livewire\DeliveryNotes;

use App\Models\DeliveryNote;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Delivery Notes — Zenaura')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        $deliveryNote = DeliveryNote::query()->findOrFail($id);
        $number = $deliveryNote->number;
        $deliveryNote->delete();

        flash_app_toast($this, 'Delivery note '.$number.' deleted. Its delivered quantities are restored to the quotation.');
    }

    public function render()
    {
        $deliveryNotes = DeliveryNote::query()
            ->with(['quotation.client', 'supplier'])
            ->withCount('items')
            ->when($this->search, function ($query) {
                $term = '%'.$this->search.'%';
                $query->where(function ($inner) use ($term) {
                    $inner->where('number', 'like', $term)
                        ->orWhereHas('quotation', fn ($quote) => $quote->where('number', 'like', $term)
                            ->orWhereHas('client', fn ($client) => $client->where('name', 'like', $term)))
                        ->orWhereHas('supplier', fn ($supplier) => $supplier->where('name', 'like', $term));
                });
            })
            ->orderByDesc('delivery_date')
            ->orderByDesc('id')
            ->paginate(15);

        return view('livewire.delivery-notes.index', compact('deliveryNotes'));
    }
}