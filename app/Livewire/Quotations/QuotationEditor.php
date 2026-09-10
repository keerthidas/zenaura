<?php

namespace App\Livewire\Quotations;

use App\Models\Client;
use App\Models\CompanySetting;
use App\Models\Product;
use App\Models\Quotation;
use App\Services\InvoiceNumberService;
use App\Services\QuotationCalculator;
use App\Services\QuotationNumberService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class QuotationEditor extends Component
{
    use WithFileUploads;

    public ?Quotation $quotation = null;

    public string $number = '';

    public string $quote_date = '';

    public string $valid_until = '';

    public ?int $client_id = null;

    public string $client_query = '';

    public array $client_results = [];

    public string $reference = '';

    public string $delivery = 'TO BE CONFIRMED';

    public string $payment_terms = 'CASH IN ADVANCE';

    public string $delivery_availability = 'EX STOCK';

    public string $delivery_details = '';

    public string $status = 'draft';

    public string $notes = '';

    public string $terms = '';

    public array $lines = [];

    public array $suggestions = [];

    public array $linePhotos = [];

    public ?int $revisingFromId = null;

    public bool $numberManual = false;

    public bool $showNewClient = false;

    public string $nc_name = '';

    public string $nc_phone = '';

    public string $nc_email = '';

    public string $nc_address = '';

    public string $nc_emirate = '';

    public string $nc_trn = '';

    protected function validationAttributes(): array
    {
        return [
            'client_id' => 'client',
        ];
    }

    public function mount(QuotationNumberService $numbers, ?Quotation $quotation = null): void
    {
        $settings = CompanySetting::current();
        $this->quote_date = now()->toDateString();
        $this->valid_until = now()->addDays((int) $settings->default_validity_days)->toDateString();
        $this->terms = (string) $settings->default_terms;

        $reviseId = (int) request('revise');
        if ($reviseId && ! $quotation?->exists) {
            $this->startRevision($reviseId, $numbers);

            return;
        }

        if ($quotation?->exists) {
            $this->quotation = $quotation->load(['items.product.images', 'client']);
            $this->number = $quotation->number;
            $this->quote_date = $quotation->quote_date->toDateString();
            $this->valid_until = optional($quotation->valid_until)?->toDateString() ?: '';
            $this->client_id = $quotation->client_id;
            $this->client_query = $quotation->client?->name ?? '';
            $this->reference = (string) $quotation->reference;
            $this->delivery = (string) ($quotation->delivery ?: 'TO BE CONFIRMED');
            $this->payment_terms = (string) ($quotation->payment_terms ?: 'CASH IN ADVANCE');
            $this->delivery_availability = (string) ($quotation->delivery_availability ?: 'EX STOCK');
            $this->delivery_details = (string) $quotation->delivery_details;
            $this->status = $quotation->status;
            $this->notes = (string) $quotation->notes;
            $this->terms = (string) $quotation->terms;
            $this->lines = $quotation->items->map(function ($item) {
                $line = $this->blankItem($item->toArray());
                $line['image'] = $this->lineImageUrl($item);

                return $line;
            })->values()->all();
        } else {
            $this->number = $numbers->preview();
            $this->lines = [$this->blankItem()];
        }

        if ($this->lines === []) {
            $this->lines = [$this->blankItem()];
        }
    }

    public function startRevision(int $sourceId, QuotationNumberService $numbers): void
    {
        $source = Quotation::with(['items.product.images', 'client'])->findOrFail($sourceId);
        $meta = $numbers->revisionOf($source);

        $this->quotation = null;
        $this->revisingFromId = $source->id;
        $this->number = $meta['number'];
        $this->numberManual = true;
        $this->quote_date = now()->toDateString();
        $this->valid_until = now()->addDays((int) CompanySetting::current()->default_validity_days)->toDateString();
        $this->client_id = $source->client_id;
        $this->client_query = $source->client?->name ?? '';
        $this->reference = (string) $source->reference;
        $this->delivery = (string) ($source->delivery ?: 'TO BE CONFIRMED');
        $this->payment_terms = (string) ($source->payment_terms ?: 'CASH IN ADVANCE');
        $this->delivery_availability = (string) ($source->delivery_availability ?: 'EX STOCK');
        $this->delivery_details = (string) $source->delivery_details;
        $this->status = 'draft';
        $this->notes = (string) $source->notes;
        $this->terms = (string) $source->terms;
        $this->lines = $source->items->map(function ($item) {
            $line = $this->blankItem($item->toArray());
            $line['image'] = $this->lineImageUrl($item);

            return $line;
        })->values()->all();

        if ($this->lines === []) {
            $this->lines = [$this->blankItem()];
        }
    }

    public function updatedNumber(): void
    {
        $this->numberManual = true;
    }

    public function updatedQuoteDate(QuotationNumberService $numbers): void
    {
        if ($this->quotation?->exists) {
            return;
        }

        if ($this->quote_date) {
            $this->valid_until = \Illuminate\Support\Carbon::parse($this->quote_date)
                ->addDays((int) CompanySetting::current()->default_validity_days)
                ->toDateString();
        }

        if (! $this->numberManual) {
            $this->number = $numbers->preview(\Illuminate\Support\Carbon::parse($this->quote_date));
        }
    }

    public function updatedClientQuery(): void
    {
        $term = trim($this->client_query);
        if ($term === '') {
            $this->client_results = [];
            $this->client_id = null;

            return;
        }

        $this->client_results = Client::query()
            ->where(function ($q) use ($term) {
                $like = '%'.$term.'%';
                $q->where('name', 'like', $like)
                    ->orWhere('trn', 'like', $like)
                    ->orWhere('phone', 'like', $like);
            })
            ->limit(8)
            ->get(['id', 'name', 'trn', 'emirate', 'address', 'phone'])
            ->toArray();
    }

    public function selectClient(int $id): void
    {
        $client = Client::findOrFail($id);
        $this->client_id = $client->id;
        $this->client_query = $client->name;
        $this->client_results = [];
        $this->delivery_details = collect([
            $client->name,
            $client->address,
            $client->emirate,
            'United Arab Emirates',
            $client->phone ? 'T: '.$client->phone : null,
        ])->filter()->implode("\n");
    }

    public function updatedLines($value, $key): void
    {
        if (! preg_match('/^(\d+)\.(code|qty|selling_price|discount_percent|vat_percent)$/', (string) $key, $m)) {
            return;
        }

        $i = (int) $m[1];
        if (! isset($this->lines[$i])) {
            return;
        }

        if ($m[2] === 'code') {
            $this->searchLineProducts($i);

            return;
        }

        $this->lines[$i] = QuotationCalculator::line($this->lines[$i]);
    }

    public function searchLineProducts(int $index): void
    {
        if (! isset($this->lines[$index])) {
            return;
        }

        $term = trim((string) $this->lines[$index]['code']);

        if ($term === '') {
            $this->lines[$index]['product_id'] = null;
            if (! $this->hasCustomLineImage($index)) {
                $this->lines[$index]['image'] = null;
                $this->lines[$index]['image_path'] = null;
            }
            unset($this->suggestions[$index]);

            return;
        }

        $exact = Product::query()
            ->where('is_active', true)
            ->whereRaw('UPPER(code) = ?', [strtoupper($term)])
            ->first();

        if ($exact) {
            $this->applyProduct($index, $exact);
            unset($this->suggestions[$index]);

            return;
        }

        $this->lines[$index]['product_id'] = null;
        if (! $this->hasCustomLineImage($index)) {
            $this->lines[$index]['image'] = null;
            $this->lines[$index]['image_path'] = null;
        }
        $this->suggestions[$index] = Product::query()
            ->where('is_active', true)
            ->where(function ($q) use ($term) {
                $like = '%'.$term.'%';
                $q->where('code', 'like', $like)->orWhere('description', 'like', $like);
            })
            ->orderBy('code')
            ->limit(8)
            ->get()
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'code' => $p->code,
                'description' => $p->description,
                'selling_price' => (string) $p->selling_price,
                'unit' => $p->unit,
            ])
            ->all();
    }

    public function pickProduct(int $index, int $productId): void
    {
        $product = Product::findOrFail($productId);
        $this->applyProduct($index, $product);
        unset($this->suggestions[$index]);
    }

    public function addLine(?int $after = null): void
    {
        $line = $this->blankItem();

        if ($after !== null && isset($this->lines[$after])) {
            $line['placing_area'] = $this->lines[$after]['placing_area'] ?? '';
            array_splice($this->lines, $after + 1, 0, [$line]);
            $this->lines = array_values($this->lines);
            $this->spliceLinePhotos($after + 1, [null]);
        } else {
            $this->lines[] = $line;
        }

        $this->suggestions = [];
    }

    public function removeLine(int $index): void
    {
        if (! isset($this->lines[$index])) {
            return;
        }

        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);
        unset($this->linePhotos[$index]);
        $this->linePhotos = array_values($this->linePhotos);
        $this->suggestions = [];

        if ($this->lines === []) {
            $this->lines = [$this->blankItem()];
        }
    }

    public function duplicateLine(int $index): void
    {
        if (! isset($this->lines[$index])) {
            return;
        }

        $copy = $this->lines[$index];
        array_splice($this->lines, $index + 1, 0, [$copy]);
        $this->lines = array_values($this->lines);
        $this->spliceLinePhotos($index + 1, [$this->linePhotos[$index] ?? null]);
        $this->suggestions = [];
    }

    public function saveNewClient(): void
    {
        $data = $this->validate([
            'nc_name' => ['required', 'string', 'max:255'],
            'nc_phone' => ['nullable', 'string', 'max:50'],
            'nc_email' => ['nullable', 'email', 'max:255'],
            'nc_address' => ['nullable', 'string', 'max:255'],
            'nc_emirate' => ['nullable', 'string', 'max:100'],
            'nc_trn' => ['required', 'string', 'max:50'],
        ]);

        $client = Client::create([
            'name' => $data['nc_name'],
            'phone' => $data['nc_phone'],
            'email' => $data['nc_email'],
            'address' => $data['nc_address'],
            'emirate' => $data['nc_emirate'],
            'trn' => $data['nc_trn'],
        ]);

        $this->selectClient($client->id);
        $this->showNewClient = false;
        $this->nc_name = $this->nc_phone = $this->nc_email = $this->nc_address = $this->nc_emirate = $this->nc_trn = '';
    }

    public function save(QuotationNumberService $numbers, InvoiceNumberService $invoices)
    {
        $items = $this->preparedLines();

        $this->validate([
            'number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('quotations', 'number')->ignore($this->quotation?->id)->whereNull('deleted_at'),
            ],
            'quote_date' => ['required', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:quote_date'],
            'client_id' => ['required', 'exists:clients,id'],
            'reference' => ['nullable', 'string', 'max:255'],
            'delivery' => ['nullable', 'string', 'max:100'],
            'payment_terms' => ['nullable', 'string', 'max:100'],
            'delivery_availability' => ['nullable', 'string', 'max:100'],
            'delivery_details' => ['nullable', 'string'],
            'status' => ['required', Rule::in(Quotation::STATUSES)],
            'notes' => ['nullable', 'string'],
            'terms' => ['nullable', 'string'],
            'linePhotos.*' => ['nullable', 'image', 'max:4096'],
        ], [
            'client_id.required' => 'Select a client (TRN is required on the client record).',
        ]);

        if ($items === []) {
            $this->addError('lines', 'Add at least one product.');

            return;
        }

        foreach ($items as $item) {
            if (trim((string) $item['code']) === '' || trim((string) $item['description']) === '') {
                $this->addError('lines', 'Every line needs a code and description.');

                return;
            }
        }

        $totals = QuotationCalculator::totals($items);
        $meta = $numbers->resolveForSave($this->number, \Illuminate\Support\Carbon::parse($this->quote_date));

        $wasNew = ! $this->quotation?->exists;

        $quotation = DB::transaction(function () use ($meta, $totals, $items, $invoices) {
            $payload = array_merge($meta, [
                'client_id' => $this->client_id,
                'quote_date' => $this->quote_date,
                'valid_until' => $this->valid_until ?: null,
                'reference' => $this->reference ?: null,
                'delivery' => $this->delivery ?: null,
                'payment_terms' => $this->payment_terms ?: null,
                'delivery_availability' => $this->delivery_availability ?: null,
                'delivery_details' => $this->delivery_details ?: null,
                'status' => $this->status,
                'notes' => $this->notes ?: null,
                'terms' => $this->terms ?: null,
                'subtotal' => $totals['subtotal'],
                'discount_total' => $totals['discount_total'],
                'vat_total' => $totals['vat_total'],
                'grand_total' => $totals['grand_total'],
            ]);

            if ($this->quotation?->exists) {
                $this->quotation->update($payload);
                $quotation = $this->quotation->fresh();
            } else {
                $payload['created_by'] = Auth::id();
                if ($this->revisingFromId) {
                    $payload['parent_id'] = $this->revisingFromId;
                }
                $quotation = Quotation::create($payload);
            }

            $quotation->items()->delete();

            $sort = 0;
            foreach ($this->lines as $index => $raw) {
                $code = trim((string) ($raw['code'] ?? ''));
                $description = trim((string) ($raw['description'] ?? ''));
                if ($code === '' && $description === '') {
                    continue;
                }

                $line = QuotationCalculator::line($raw);
                $product = $this->ensureCatalogueProduct($line);
                $imagePath = $this->persistLineImage($quotation, $product, (int) $index, $line['image_path'] ?? null);

                $quotation->items()->create([
                    'product_id' => $product->id,
                    'heading' => trim((string) ($line['heading'] ?? '')) ?: null,
                    'placing_area' => trim((string) ($line['placing_area'] ?? '')) ?: null,
                    'image_path' => $imagePath,
                    'code' => strtoupper(trim($line['code'])),
                    'description' => $line['description'],
                    'unit' => $line['unit'] ?: 'EACH',
                    'qty' => ($line['qty'] === '' || $line['qty'] === null) ? 1 : $line['qty'],
                    'selling_price' => $line['selling_price'],
                    'discount_percent' => $line['discount_percent'] ?: 0,
                    'vat_percent' => $line['vat_percent'] ?: 0,
                    'amount' => $line['amount'],
                    'vat_amount' => $line['vat_amount'],
                    'line_total' => $line['line_total'],
                    'sort_order' => ++$sort,
                ]);
            }

            if ($quotation->status === 'accepted') {
                $quotation->forceFill($invoices->allocate($quotation))->save();
            }

            return $quotation;
        });

        $this->quotation = $quotation;
        $this->number = $quotation->number;
        flash_app_toast($this, [
            'eyebrow' => $wasNew ? 'Quotation created' : 'Quotation updated',
            'title' => $quotation->number,
            'body' => $wasNew
                ? 'Saved. You can open the PDF or keep editing.'
                : 'All changes are on file.',
        ]);

        return $this->redirect(route('quotations.edit', $quotation), navigate: true);
    }

    #[Computed]
    public function totals(): array
    {
        return QuotationCalculator::totals($this->preparedLines());
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function preparedLines(): array
    {
        $items = [];

        foreach ($this->lines as $item) {
            $code = trim((string) ($item['code'] ?? ''));
            $description = trim((string) ($item['description'] ?? ''));

            if ($code === '' && $description === '') {
                continue;
            }

            $items[] = QuotationCalculator::line($item);
        }

        return $items;
    }

    protected function ensureCatalogueProduct(array $line): Product
    {
        $code = strtoupper(trim((string) $line['code']));

        $product = Product::withTrashed()
            ->whereRaw('UPPER(code) = ?', [$code])
            ->first();

        if ($product) {
            if ($product->trashed()) {
                $product->restore();
            }

            return $product;
        }

        return Product::create([
            'code' => $code,
            'description' => $line['description'],
            'actual_price' => 0,
            'selling_price' => $line['selling_price'] ?: 0,
            'unit' => $line['unit'] ?: 'EACH',
            'is_active' => true,
        ]);
    }

    protected function applyProduct(int $index, Product $product): void
    {
        $this->lines[$index]['product_id'] = $product->id;
        $this->lines[$index]['code'] = $product->code;
        $this->lines[$index]['description'] = $product->description;
        $this->lines[$index]['unit'] = strtoupper($product->unit ?: 'EACH');
        $this->lines[$index]['selling_price'] = (string) $product->selling_price;
        if (! $this->hasCustomLineImage($index)) {
            $product->loadMissing('images');
            $this->lines[$index]['image'] = $product->images->first()?->url();
            $this->lines[$index]['image_path'] = null;
        }
        $this->lines[$index] = QuotationCalculator::line($this->lines[$index]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function blankItem(array $overrides = []): array
    {
        $settings = CompanySetting::current();

        $line = QuotationCalculator::line(array_merge([
            'product_id' => null,
            'heading' => '',
            'placing_area' => '',
            'image' => null,
            'image_path' => null,
            'code' => '',
            'description' => '',
            'unit' => strtoupper($settings->default_unit ?: 'EACH'),
            'qty' => '',
            'selling_price' => '',
            'discount_percent' => '',
            'vat_percent' => (string) $settings->default_vat_percent,
            'amount' => '0.00',
            'net' => '0.00',
            'unit_net' => '0.00',
            'vat_amount' => '0.00',
            'line_total' => '0.00',
        ], $overrides));

        if ((float) ($line['discount_percent'] ?? 0) == 0.0) {
            $line['discount_percent'] = '';
        }

        if ((float) ($line['selling_price'] ?? 0) == 0.0 && trim((string) ($line['code'] ?? '')) === '') {
            $line['selling_price'] = '';
        }

        foreach (['qty', 'selling_price', 'discount_percent'] as $field) {
            if (! isset($line[$field]) || $line[$field] === '' || $line[$field] === null) {
                continue;
            }

            $line[$field] = rtrim(rtrim(number_format((float) $line[$field], 3, '.', ''), '0'), '.');
        }

        return $line;
    }

    public function updatedLinePhotos($value, $key): void
    {
        $index = (int) $key;

        if (! isset($this->lines[$index], $this->linePhotos[$index]) || ! $this->linePhotos[$index]) {
            return;
        }

        $this->validateOnly('linePhotos.'.$index, [
            'linePhotos.'.$index => ['image', 'max:4096'],
        ]);

        $this->lines[$index]['image'] = $this->linePhotos[$index]->temporaryUrl();
    }

    protected function hasCustomLineImage(int $index): bool
    {
        return ! empty($this->lines[$index]['image_path'] ?? null)
            || ! empty($this->linePhotos[$index]);
    }

    protected function lineImageUrl(\App\Models\QuotationItem $item): ?string
    {
        if ($item->image_path) {
            return public_media_url($item->image_path);
        }

        return $item->product?->images->first()?->url();
    }

    /**
     * @param  list<mixed>  $photos
     */
    protected function spliceLinePhotos(int $offset, array $photos): void
    {
        $current = array_values($this->linePhotos);
        array_splice($current, $offset, 0, $photos);
        $this->linePhotos = $current;
    }

    protected function persistLineImage(Quotation $quotation, Product $product, int $index, ?string $existing): ?string
    {
        $photo = $this->linePhotos[$index] ?? null;
        $path = $existing ?: null;

        if ($photo) {
            $path = $photo->store('quotation-items/'.$quotation->id, 'public');
        }

        if ($path && $product->images()->doesntExist()) {
            $product->images()->create([
                'path' => $path,
                'sort_order' => 1,
            ]);
        }

        return $path ?: null;
    }

    public function render()
    {
        $title = ($this->quotation?->exists ? 'Edit '.$this->quotation->number : 'New quotation').' — Zenaura';

        return view('livewire.quotations.quotation-editor')
            ->title($title);
    }
}
