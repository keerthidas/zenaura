<?php

namespace App\Livewire\Settings;

use App\Models\CompanySetting;
use Illuminate\Http\UploadedFile;
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

    public $signature = null;

    public $stamp = null;

    public ?string $existingLogo = null;

    public ?string $existingSignature = null;

    public ?string $existingStamp = null;

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
        $this->existingSignature = $settings->signature_path;
        $this->existingStamp = $settings->stamp_path;
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
            'signature' => ['nullable', 'image', 'max:4096'],
            'stamp' => ['nullable', 'image', 'max:4096'],
        ]);

        $settings = CompanySetting::current();
        unset($data['logo'], $data['signature'], $data['stamp']);

        $this->existingLogo = $this->storeBrandingImage($this->logo, $settings->logo_path, 'logo_path', $data);
        $this->existingSignature = $this->storeBrandingImage($this->signature, $settings->signature_path, 'signature_path', $data);
        $this->existingStamp = $this->storeBrandingImage($this->stamp, $settings->stamp_path, 'stamp_path', $data);

        $this->logo = null;
        $this->signature = null;
        $this->stamp = null;

        $settings->update($data);
        flash_app_toast($this, 'Settings saved.');
    }

    public function useDefaultLogo(): void
    {
        $this->clearBrandingImage('logo_path', 'existingLogo');
    }

    public function useDefaultSignature(): void
    {
        $this->clearBrandingImage('signature_path', 'existingSignature');
    }

    public function useDefaultStamp(): void
    {
        $this->clearBrandingImage('stamp_path', 'existingStamp');
    }

    public function render()
    {
        return view('livewire.settings.company-form');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function storeBrandingImage(mixed $upload, ?string $currentPath, string $column, array &$data): ?string
    {
        if (! $upload instanceof UploadedFile) {
            return $currentPath;
        }

        if ($currentPath) {
            Storage::disk('public')->delete($currentPath);
        }

        $path = $upload->store('company', 'public');
        $data[$column] = $path;

        return $path;
    }

    private function clearBrandingImage(string $column, string $existingProperty): void
    {
        $settings = CompanySetting::current();
        $path = $settings->{$column};
        if ($path) {
            Storage::disk('public')->delete($path);
            $settings->update([$column => null]);
        }
        $this->{$existingProperty} = null;
    }
}
