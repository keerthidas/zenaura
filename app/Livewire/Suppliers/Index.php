<?php

namespace App\Livewire\Suppliers;

use App\Models\Supplier;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Suppliers — Zenaura')]
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
        $supplier = Supplier::findOrFail($id);

        if ($supplier->purchaseOrders()->withTrashed()->exists()) {
            $supplier->delete();
            session()->flash('success', 'Supplier archived because LPOs exist.');

            return;
        }

        $supplier->forceDelete();
        session()->flash('success', 'Supplier deleted.');
    }

    public function render()
    {
        $suppliers = Supplier::query()
            ->withCount(['purchaseOrders' => fn ($q) => $q->issued()])
            ->when($this->search, function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('trn', 'like', $term)
                        ->orWhere('phone', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('contact_person', 'like', $term);
                });
            })
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.suppliers.index', compact('suppliers'));
    }
}
