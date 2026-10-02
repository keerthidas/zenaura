<?php

namespace App\Livewire\PurchaseOrders;

use App\Livewire\Suppliers\SupplierForm;
use App\Models\CompanySetting;
use App\Models\PurchaseOrder;
use App\Models\Quotation;
use App\Models\Supplier;
use App\Services\PurchaseOrderService;
use App\Services\QuotationCalculator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class PurchaseOrderEditor extends Component
{
    public Quotation $quotation;

    public ?int $replacingId = null;

    /** @var int|string|null */
    public $supplier_id = null;

    public string $lpo_date = '';

    public string $supplier_quote_number = '';

    public string $supplier_quote_date = '';

    public string $customer_reference = '';

    public string $payment_terms = PurchaseOrder::DEFAULT_PAYMENT_TERMS;

    public string $delivery_terms = PurchaseOrder::DEFAULT_DELIVERY_TERMS;

    public string $delivery_availability = PurchaseOrder::DEFAULT_DELIVERY_AVAILABILITY;

    public string $buyer_details = '';

    public string $terms = '';

    public string $notes = '';

    public array $lines = [];

    public string $bulk_discount = '';

    public bool $showNewSupplier = false;

    public string $ns_name = '';

    public string $ns_contact_person = '';

    public string $ns_address = '';

    public string $ns_phone = '';

    public string $ns_email = '';

    public string $ns_trn = '';

    protected function validationAttributes(): array
    {
        return [
            'supplier_id' => 'supplier',
            'lpo_date' => 'LPO date',
            'ns_name' => 'supplier name',
            'ns_email' => 'supplier email',
        ];
    }

    public function mount(Quotation $quotation, PurchaseOrderService $service): void
    {
        $this->quotation = $quotation->load(['client', 'items']);
        $this->lpo_date = now()->toDateString();
        $this->customer_reference = (string) $quotation->reference;
        $this->delivery_availability = (string) ($quotation->delivery_availability ?: PurchaseOrder::DEFAULT_DELIVERY_AVAILABILITY);
        $this->buyer_details = PurchaseOrder::defaultBuyerDetails();

        $replacing = null;
        $replaceId = (int) request('replace');
        if ($replaceId) {
            $replacing = PurchaseOrder::query()
                ->with('items')
                ->where('quotation_id', $quotation->id)
                ->findOrFail($replaceId);

            if (! $replacing->isIssued()) {
                session()->flash('error', 'LPO '.$replacing->number.' is already cancelled.');
                $this->redirect(route('purchase-orders.index', ['quotation' => $quotation->id]), navigate: true);

                return;
            }

            $this->replacingId = $replacing->id;
            $this->supplier_id = $replacing->supplier_id;
            $this->supplier_quote_number = (string) $replacing->supplier_quote_number;
            $this->supplier_quote_date = (string) $replacing->supplier_quote_date?->toDateString();
            $this->customer_reference = (string) $replacing->customer_reference;
            $this->payment_terms = (string) $replacing->payment_terms;
            $this->delivery_terms = (string) $replacing->delivery_terms;
            $this->delivery_availability = (string) $replacing->delivery_availability;
            $this->buyer_details = (string) ($replacing->buyer_details ?: $this->buyer_details);
            $this->terms = (string) $replacing->terms;
            $this->notes = (string) $replacing->notes;
        }

        $this->lines = $this->quotationLines($service->orderedQuantities($quotation, $replacing?->id));

        if ($replacing) {
            $this->overlayExistingItems($replacing);
        }
    }

    /**
     * @param  array<string, float>  $ordered
     * @return list<array<string, mixed>>
     */
    protected function quotationLines(array $ordered): array
    {
        $pool = $ordered;
        $lines = [];

        foreach ($this->quotation->items as $item) {
            $codeKey = strtoupper(trim((string) $item->code));
            $quotedQty = (float) $item->qty;
            $alreadyOrdered = min($quotedQty, max(0, $pool[$codeKey] ?? 0));
            if ($codeKey !== '') {
                $pool[$codeKey] = round(($pool[$codeKey] ?? 0) - $alreadyOrdered, 3);
            }
            $remaining = round($quotedQty - $alreadyOrdered, 3);

            $lines[] = $this->blankLine([
                'include' => $remaining > 0,
                'from_quote' => true,
                'product_id' => $item->product_id,
                'heading' => trim(collect([$item->heading, $item->placing_area])->filter()->implode(' · ')),
                'code' => (string) $item->code,
                'description' => (string) $item->description,
                'unit' => strtoupper((string) ($item->unit ?: 'EACH')),
                'quoted_qty' => $quotedQty,
                'ordered_elsewhere' => $alreadyOrdered,
                'qty' => self::trimNumber($remaining > 0 ? $remaining : $quotedQty),
                'quoted_price' => (string) $item->selling_price,
                'quoted_discount' => (float) $item->discount_percent,
                'unit_price' => self::trimNumber((float) $item->selling_price),
                'discount_percent' => (float) $item->discount_percent > 0 ? self::trimNumber((float) $item->discount_percent) : '',
                'vat_percent' => self::trimNumber((float) $item->vat_percent),
            ]);
        }

        return $lines;
    }

    protected function overlayExistingItems(PurchaseOrder $order): void
    {
        foreach ($this->lines as $i => $line) {
            $this->lines[$i]['include'] = false;
        }

        $used = [];
        foreach ($order->items as $item) {
            $codeKey = strtoupper(trim((string) $item->code));
            $match = null;

            foreach ($this->lines as $i => $line) {
                if (! isset($used[$i]) && $line['from_quote'] && $codeKey !== '' && strtoupper(trim((string) $line['code'])) === $codeKey) {
                    $match = $i;
                    break;
                }
            }

            $values = [
                'include' => true,
                'description' => (string) $item->description,
                'unit' => (string) $item->unit,
                'qty' => self::trimNumber((float) $item->qty),
                'unit_price' => self::trimNumber((float) $item->unit_price),
                'discount_percent' => (float) $item->discount_percent > 0 ? self::trimNumber((float) $item->discount_percent) : '',
                'vat_percent' => self::trimNumber((float) $item->vat_percent),
            ];

            if ($match !== null) {
                $used[$match] = true;
                $this->lines[$match] = PurchaseOrderService::calculateLine(array_merge($this->lines[$match], $values));

                continue;
            }

            $this->lines[] = $this->blankLine(array_merge($values, [
                'product_id' => $item->product_id,
                'code' => (string) $item->code,
                'quoted_price' => (string) $item->quoted_price,
            ]));
        }
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function blankLine(array $overrides = []): array
    {
        $settings = CompanySetting::current();

        return PurchaseOrderService::calculateLine(array_merge([
            'key' => (string) Str::uuid(),
            'include' => true,
            'from_quote' => false,
            'product_id' => null,
            'heading' => '',
            'code' => '',
            'description' => '',
            'unit' => strtoupper($settings->default_unit ?: 'EACH'),
            'quoted_qty' => 0,
            'ordered_elsewhere' => 0,
            'qty' => '1',
            'quoted_price' => '0',
            'quoted_discount' => 0,
            'unit_price' => '',
            'discount_percent' => '',
            'vat_percent' => self::trimNumber((float) $settings->default_vat_percent),
        ], $overrides));
    }

    public static function trimNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 3, '.', ''), '0'), '.');
    }

    public function updatedLines($value, $key): void
    {
        if (! preg_match('/^(\d+)\.(qty|unit_price|discount_percent|vat_percent)$/', (string) $key, $m)) {
            return;
        }

        $i = (int) $m[1];
        if (! isset($this->lines[$i])) {
            return;
        }

        $raw = trim((string) ($this->lines[$i][$m[2]] ?? ''));
        if ($raw !== '' && preg_match('/[,\s]/', $raw)) {
            $this->lines[$i][$m[2]] = str_replace([',', ' '], '', $raw);
        }

        $this->lines[$i] = PurchaseOrderService::calculateLine($this->lines[$i]);
    }

    public function resetPrice(int $index): void
    {
        if (! isset($this->lines[$index])) {
            return;
        }

        $this->lines[$index]['unit_price'] = self::trimNumber((float) $this->lines[$index]['quoted_price']);
        $this->lines[$index] = PurchaseOrderService::calculateLine($this->lines[$index]);
    }

    public function toggleAll(bool $include): void
    {
        foreach ($this->lines as $i => $line) {
            $this->lines[$i]['include'] = $include;
        }
    }

    public function applyDiscountToSelected(): void
    {
        $this->validate([
            'bulk_discount' => ['required', 'numeric', 'min:0', 'max:100'],
        ], [], ['bulk_discount' => 'discount']);

        $display = self::trimNumber(QuotationCalculator::number($this->bulk_discount));

        foreach ($this->lines as $i => $line) {
            if (! $line['include']) {
                continue;
            }
            $this->lines[$i]['discount_percent'] = $display;
            $this->lines[$i] = PurchaseOrderService::calculateLine($this->lines[$i]);
        }

        $this->bulk_discount = $display;
    }

    public function addLine(): void
    {
        $this->lines[] = $this->blankLine();
    }

    public function removeLine(int $index): void
    {
        if (! isset($this->lines[$index]) || $this->lines[$index]['from_quote']) {
            return;
        }

        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);
    }

    public function saveNewSupplier(): void
    {
        $data = $this->validate(collect(SupplierForm::rules('ns_'))->except('ns_notes')->all());

        $supplier = Supplier::create([
            'name' => $data['ns_name'],
            'contact_person' => $data['ns_contact_person'] ?: null,
            'address' => $data['ns_address'] ?: null,
            'phone' => $data['ns_phone'] ?: null,
            'email' => $data['ns_email'] ?: null,
            'trn' => $data['ns_trn'] ?: null,
        ]);

        $this->supplier_id = $supplier->id;
        $this->showNewSupplier = false;
        $this->reset(['ns_name', 'ns_contact_person', 'ns_address', 'ns_phone', 'ns_email', 'ns_trn']);
        $this->resetErrorBag('supplier_id');
    }

    public function save(PurchaseOrderService $service)
    {
        try {
            $this->validate([
                'supplier_id' => ['required', 'exists:suppliers,id'],
                'lpo_date' => ['required', 'date'],
                'supplier_quote_number' => ['nullable', 'string', 'max:100'],
                'supplier_quote_date' => ['nullable', 'date'],
                'customer_reference' => ['nullable', 'string', 'max:255'],
                'payment_terms' => ['required', 'string', 'max:150'],
                'delivery_terms' => ['required', 'string', 'max:150'],
                'delivery_availability' => ['required', 'string', 'max:150'],
                'buyer_details' => ['nullable', 'string', 'max:1000'],
                'terms' => ['nullable', 'string', 'max:5000'],
                'notes' => ['nullable', 'string', 'max:5000'],
            ], [
                'supplier_id.required' => 'Select a supplier.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            flash_app_toast($this, 'Could not issue the LPO.', 'error');
            throw $e;
        }

        $selected = $this->selectedLines();

        if ($selected === []) {
            $this->addError('lines', 'Select at least one line for this LPO.');
            flash_app_toast($this, 'Could not issue the LPO.', 'error');

            return;
        }

        foreach ($selected as $line) {
            $qty = QuotationCalculator::number($line['qty'] ?? 0);
            $price = trim((string) ($line['unit_price'] ?? ''));
            $discount = QuotationCalculator::number($line['discount_percent'] ?? 0);
            $label = trim((string) $line['code']) ?: 'a custom line';

            $error = match (true) {
                trim((string) $line['description']) === '' => 'Every selected line needs a description.',
                $qty <= 0 => 'Enter a quantity above zero for '.$label.'.',
                $price === '' || ! is_numeric(str_replace([',', ' '], '', $price)) || QuotationCalculator::number($price) < 0 => 'Enter a valid unit price for '.$label.'.',
                $discount < 0 || $discount > 100 => 'Discount for '.$label.' must be between 0 and 100.',
                default => null,
            };

            if ($error) {
                $this->addError('lines', $error);
                flash_app_toast($this, 'Could not issue the LPO.', 'error');

                return;
            }
        }

        $replacing = $this->replacingId ? PurchaseOrder::find($this->replacingId) : null;

        try {
            $order = $service->issue($this->quotation, [
                'supplier_id' => (int) $this->supplier_id,
                'lpo_date' => $this->lpo_date,
                'supplier_quote_number' => trim($this->supplier_quote_number) ?: null,
                'supplier_quote_date' => $this->supplier_quote_date ?: null,
                'customer_reference' => trim($this->customer_reference) ?: null,
                'payment_terms' => trim($this->payment_terms),
                'delivery_terms' => trim($this->delivery_terms),
                'delivery_availability' => trim($this->delivery_availability),
                'buyer_details' => trim($this->buyer_details) ?: null,
                'terms' => trim($this->terms) ?: null,
                'notes' => trim($this->notes) ?: null,
            ], $selected, $replacing);
        } catch (\InvalidArgumentException $e) {
            $this->addError('lines', $e->getMessage());
            flash_app_toast($this, 'Could not issue the LPO.', 'error');

            return;
        }

        $message = $replacing
            ? 'LPO '.$order->number.' issued. '.$replacing->number.' was cancelled.'
            : 'LPO '.$order->number.' issued.';
        session()->flash('toast', ['message' => $message, 'type' => 'success']);

        return $this->redirect(route('purchase-orders.index', ['quotation' => $this->quotation->id, 'open' => $order->id]), navigate: true);
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function selectedLines(): array
    {
        return collect($this->lines)
            ->filter(fn ($line) => ! empty($line['include']))
            ->map(fn ($line) => PurchaseOrderService::calculateLine($line))
            ->values()
            ->all();
    }

    #[Computed]
    public function totals(): array
    {
        return PurchaseOrderService::totals($this->selectedLines());
    }

    #[Computed]
    public function quotedTotals(): array
    {
        $quoted = collect($this->selectedLines())
            ->map(fn ($line) => array_merge($line, [
                'unit_price' => $line['quoted_price'],
                'discount_percent' => $line['quoted_discount'] ?? 0,
            ]))
            ->all();

        return PurchaseOrderService::totals($quoted);
    }

    #[Computed]
    public function numberPreview(): string
    {
        $date = $this->lpo_date ? rescue(fn () => Carbon::parse($this->lpo_date), now(), false) : now();

        return app(PurchaseOrderService::class)->preview($date);
    }

    public function render()
    {
        $replacing = $this->replacingId ? PurchaseOrder::find($this->replacingId) : null;

        return view('livewire.purchase-orders.purchase-order-editor', [
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name']),
            'replacing' => $replacing,
            'otherOrders' => $this->quotation->purchaseOrders()
                ->with('supplier')
                ->issued()
                ->when($this->replacingId, fn ($q) => $q->where('id', '!=', $this->replacingId))
                ->get(),
        ])->title(($replacing ? 'Re-issue '.$replacing->number : 'New LPO').' — Zenaura');
    }
}
