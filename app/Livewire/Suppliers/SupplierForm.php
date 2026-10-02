<?php

namespace App\Livewire\Suppliers;

use App\Models\Supplier;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class SupplierForm extends Component
{
    public ?Supplier $supplier = null;

    public string $name = '';

    public string $contact_person = '';

    public string $address = '';

    public string $phone = '';

    public string $email = '';

    public string $trn = '';

    public string $notes = '';

    public function mount(?Supplier $supplier = null): void
    {
        if ($supplier?->exists) {
            $this->supplier = $supplier;
            $this->fill(array_map(
                fn ($value) => (string) $value,
                $supplier->only(['name', 'contact_person', 'address', 'phone', 'email', 'trn', 'notes'])
            ));
        }
    }

    public function save(): void
    {
        $data = $this->validate(self::rules());

        Supplier::updateOrCreate(['id' => $this->supplier?->id], array_map(
            fn ($value) => is_string($value) && trim($value) === '' ? null : $value,
            $data
        ));

        session()->flash('success', 'Supplier saved.');
        $this->redirect(route('suppliers.index'), navigate: true);
    }

    /**
     * @return array<string, list<string>>
     */
    public static function rules(string $prefix = ''): array
    {
        return [
            $prefix.'name' => ['required', 'string', 'max:255'],
            $prefix.'contact_person' => ['nullable', 'string', 'max:255'],
            $prefix.'address' => ['nullable', 'string', 'max:1000'],
            $prefix.'phone' => ['nullable', 'string', 'max:50'],
            $prefix.'email' => ['nullable', 'email', 'max:255'],
            $prefix.'trn' => ['nullable', 'string', 'max:50'],
            $prefix.'notes' => ['nullable', 'string'],
        ];
    }

    public function render()
    {
        return view('livewire.suppliers.supplier-form')
            ->title(($this->supplier?->exists ? 'Edit supplier' : 'New supplier').' — Zenaura');
    }
}
