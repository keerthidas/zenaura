<?php

namespace App\Livewire\Products;

use App\Models\CompanySetting;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class ProductForm extends Component
{
    use WithFileUploads;

    public ?Product $product = null;

    public string $code = '';

    public string $description = '';

    public string $actual_price = '0.00';

    public string $selling_price = '0.00';

    public string $unit = 'pcs';

    public bool $is_active = true;

    public array $photos = [];

    public function mount(?Product $product = null): void
    {
        $this->unit = CompanySetting::current()->default_unit ?: 'pcs';

        if ($product?->exists) {
            $this->product = $product;
            $this->fill($product->only(['code', 'description', 'unit', 'is_active']));
            $this->actual_price = (string) $product->actual_price;
            $this->selling_price = (string) $product->selling_price;
        }
    }

    public function save(): void
    {
        $data = $this->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('products', 'code')->ignore($this->product?->id)->whereNull('deleted_at'),
            ],
            'description' => ['required', 'string', 'max:2000'],
            'actual_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'unit' => ['required', 'string', 'max:30'],
            'is_active' => ['boolean'],
            'photos' => ['nullable', 'array', 'max:8'],
            'photos.*' => ['image', 'max:4096'],
        ]);

        $data['code'] = strtoupper(trim($data['code']));
        unset($data['photos']);

        $product = Product::updateOrCreate(
            ['id' => $this->product?->id],
            $data
        );

        foreach ($this->photos as $index => $photo) {
            $path = $photo->store('products/'.$product->id, 'public');
            $product->images()->create([
                'path' => $path,
                'sort_order' => ($product->images()->max('sort_order') ?? 0) + $index + 1,
            ]);
        }

        $this->photos = [];
        $this->product = $product->fresh('images');

        session()->flash('success', 'Product saved.');
        $this->redirect(route('products.index'), navigate: true);
    }

    public function removeImage(int $id): void
    {
        $image = ProductImage::where('product_id', $this->product?->id)->findOrFail($id);
        Storage::disk('public')->delete($image->path);
        $image->delete();
        $this->product = $this->product?->fresh('images');
    }

    public function title(): string
    {
        return ($this->product?->exists ? 'Edit product' : 'New product').' — Zenaura';
    }

    public function render()
    {
        return view('livewire.products.product-form')
            ->title($this->title());
    }
}
