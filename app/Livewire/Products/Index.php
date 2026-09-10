<?php

namespace App\Livewire\Products;

use App\Models\Product;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Products — Zenaura')]
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
        $product = Product::findOrFail($id);

        if ($product->quotationItems()->exists()) {
            $product->delete();
            session()->flash('success', 'Product archived because it is used on quotations.');

            return;
        }

        $product->forceDelete();
        session()->flash('success', 'Product deleted.');
    }

    public function render()
    {
        $products = Product::query()
            ->with('images')
            ->when($this->search, function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('code', 'like', $term)
                        ->orWhere('description', 'like', $term);
                });
            })
            ->latest()
            ->paginate(12);

        return view('livewire.products.index', compact('products'));
    }
}
