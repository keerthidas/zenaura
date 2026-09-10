<?php

namespace App\Livewire\Settings;

use App\Models\CompanySetting;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Company settings — Zenaura')]
class CompanyForm extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $address = '';

    public string $emirate = '';

    public string $phone = '';

    public string $email = '';

    public string $website = '';

    public string $trn = '';

    public string $quote_prefix = 'ZN';

    public string $invoice_prefix = 'ZS';

    public string $default_vat_percent = '5.00';

    public int $default_validity_days = 30;

    public string $default_unit = 'pcs';

    public string $currency = 'AED';

    public string $default_terms = '';

    public string $bank_details = '';

    public $logo = null;

    public ?string $existingLogo = null;

    public function mount(): void
    {
        $settings = CompanySetting::current();
        $this->fill($settings->only([
            'name', 'address', 'emirate', 'phone', 'email', 'trn',
            'quote_prefix', 'invoice_prefix', 'default_unit', 'currency', 'default_terms',
        ]));
        $this->default_vat_percent = (string) $settings->default_vat_percent;
        $this->default_validity_days = (int) $settings->default_validity_days;
        $this->existingLogo = $settings->logo_path;
        $this->website = (string) ($settings->website ?? '');
        $this->bank_details = (string) ($settings->bank_details ?? '');
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'emirate' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'bank_details' => ['nullable', 'string'],
            'trn' => ['nullable', 'string', 'max:50'],
            'quote_prefix' => ['required', 'string', 'max:20'],
            'invoice_prefix' => ['required', 'string', 'max:20'],
            'default_vat_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'default_validity_days' => ['required', 'integer', 'min:1', 'max:365'],
            'default_unit' => ['required', 'string', 'max:30'],
            'currency' => ['required', 'string', 'max:10'],
            'default_terms' => ['nullable', 'string'],
            'logo' => ['nullable', 'image', 'max:4096'],
        ]);

        $settings = CompanySetting::current();
        unset($data['logo']);

        if ($this->logo) {
            if ($settings->logo_path) {
                Storage::disk('public')->delete($settings->logo_path);
            }
            $data['logo_path'] = $this->logo->store('company', 'public');
            $this->existingLogo = $data['logo_path'];
            $this->logo = null;
        }

        $settings->update($data);
        session()->flash('success', 'Company settings saved.');
    }

    public function render()
    {
        return view('livewire.settings.company-form');
    }
}
