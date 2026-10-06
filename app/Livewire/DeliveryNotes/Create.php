<?php

namespace App\Livewire\DeliveryNotes;

use App\Livewire\Suppliers\SupplierForm;
use App\Models\DeliveryNote;
use App\Models\Quotation;
use App\Models\Supplier;
use App\Services\DeliveryNoteService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Create extends Component
{
    public string $quotationId = '';

    public string $supplier_id = '';

    public string $deliveryDate = '';

    public string $notes = '';

    public array $lines = [];

    public bool $showNewSupplier = false;

    public string $ns_name = '';

    public string $ns_contact_person = '';

    public string $ns_address = '';

    public string $ns_phone = '';

    public string $ns_email = '';

    public string $ns_trn = '';

    public function mount(DeliveryNoteService $deliveryNotes): void
    {
        $this->deliveryDate = now()->toDateString();
        $requestedQuotation = request()->query('quotation');

        if (is_numeric($requestedQuotation)) {
            $quotation = Quotation::query()
                ->where('status', 'accepted')
                ->find($requestedQuotation);

            if ($quotation) {
                $this->quotationId = (string) $quotation->id;
                $this->loadQuotationLines($deliveryNotes);
            }
        }
    }

    public function updatedQuotationId(DeliveryNoteService $deliveryNotes): void
    {
        $this->lines = [];
        $this->resetErrorBag(['lines', 'quotationId']);

        if ($this->quotationId !== '') {
            $this->loadQuotationLines($deliveryNotes);
        }
    }

    protected function loadQuotationLines(DeliveryNoteService $deliveryNotes): void
    {
        $quotation = Quotation::query()
            ->with('items')
            ->where('status', 'accepted')
            ->find($this->quotationId);
        if (! $quotation) {
            $this->lines = [];

            return;
        }

        $delivered = $deliveryNotes->deliveredQuantities($quotation);
        $this->lines = $quotation->items->map(function ($item) use ($delivered) {
            $quoted = (float) $item->qty;
            $alreadyDelivered = $delivered[$item->id] ?? 0;
            $remaining = max(0, $quoted - $alreadyDelivered);

            return [
                'quotation_item_id' => $item->id,
                'include' => $remaining > 0,
                'code' => (string) $item->code,
                'description' => (string) $item->description,
                'unit' => (string) $item->unit,
                'quoted_qty' => $quoted,
                'delivered_before' => $alreadyDelivered,
                'remaining_qty' => $remaining,
                'delivered_qty' => self::formatQuantity($remaining),
            ];
        })->all();
    }

    public static function formatQuantity(float $quantity): string
    {
        return rtrim(rtrim(number_format($quantity, 3, '.', ''), '0'), '.');
    }

    public function saveNewSupplier(): void
    {
        $data = $this->validate(collect(SupplierForm::rules('ns_'))->except('ns_notes')->all());
        $supplier = Supplier::query()->create([
            'name' => trim($data['ns_name']),
            'contact_person' => trim($data['ns_contact_person']) ?: null,
            'address' => trim($data['ns_address']) ?: null,
            'phone' => trim($data['ns_phone']) ?: null,
            'email' => trim($data['ns_email']) ?: null,
            'trn' => trim($data['ns_trn']) ?: null,
        ]);

        $this->supplier_id = (string) $supplier->id;
        $this->showNewSupplier = false;
        $this->reset(['ns_name', 'ns_contact_person', 'ns_address', 'ns_phone', 'ns_email', 'ns_trn']);
    }

    public function save(DeliveryNoteService $deliveryNotes)
    {
        $this->validate([
            'quotationId' => ['required', 'integer', Rule::exists('quotations', 'id')->where('status', 'accepted')->whereNull('deleted_at')],
            'supplier_id' => ['required', 'integer', Rule::exists('suppliers', 'id')->whereNull('deleted_at')],
            'deliveryDate' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array'],
            'lines.*.include' => ['boolean'],
            'lines.*.quotation_item_id' => ['required', 'integer'],
            'lines.*.delivered_qty' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
        ], [], [
            'quotationId' => 'quotation',
            'supplier_id' => 'supplier',
            'deliveryDate' => 'delivery date',
        ]);

        $selectedLines = collect($this->lines)
            ->filter(fn ($line) => filter_var($line['include'] ?? false, FILTER_VALIDATE_BOOLEAN)
                && is_numeric($line['delivered_qty'] ?? null)
                && (float) $line['delivered_qty'] > 0)
            ->map(fn ($line) => [
                'quotation_item_id' => (int) $line['quotation_item_id'],
                'delivered_qty' => (float) $line['delivered_qty'],
            ])
            ->values()
            ->all();

        if ($selectedLines === []) {
            $this->addError('lines', 'Select at least one material and enter a delivered quantity greater than zero.');

            return;
        }

        $quotation = Quotation::query()
            ->with('items')
            ->where('status', 'accepted')
            ->findOrFail((int) $this->quotationId);
        $validItemIds = $quotation->items->modelKeys();
        if (collect($selectedLines)->contains(fn ($line) => ! in_array($line['quotation_item_id'], $validItemIds, true))) {
            $this->addError('lines', 'One or more selected materials do not belong to this quotation.');

            return;
        }

        $note = $deliveryNotes->create(
            $quotation,
            Supplier::query()->findOrFail((int) $this->supplier_id),
            $this->deliveryDate,
            $selectedLines,
            trim($this->notes) ?: null,
        );

        $excessCount = $note->items->filter(fn ($item) => (float) $item->delivered_before + (float) $item->delivered_qty > (float) $item->quoted_qty)->count();
        $message = 'Delivery note '.$note->number.' created.';
        if ($excessCount > 0) {
            $message .= ' '.$excessCount.' line(s) exceed the quoted quantity; the note was saved.';
        }

        flash_app_toast($this, $message);
        $this->redirect(route('delivery-notes.index'), navigate: true);
    }

    public function render()
    {
        $quotations = Quotation::query()
            ->with('client')
            ->where('status', 'accepted')
            ->orderByDesc('quote_date')
            ->get();
        $suppliers = Supplier::query()->orderBy('name')->get();

        return view('livewire.delivery-notes.create', compact('quotations', 'suppliers'))
            ->title('New delivery note — Zenaura');
    }
}