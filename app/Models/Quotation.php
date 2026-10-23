<?php

namespace App\Models;

use App\Services\InvoiceNumberService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quotation extends Model
{
    use SoftDeletes;

    public const STATUSES = ['draft', 'sent', 'accepted', 'rejected', 'expired'];

    protected $fillable = [
        'number',
        'base_number',
        'revision',
        'sequence_year',
        'sequence_month',
        'sequence_number',
        'parent_id',
        'client_id',
        'quote_date',
        'valid_until',
        'reference',
        'salesperson',
        'sales_admin',
        'delivery',
        'payment_terms',
        'delivery_availability',
        'delivery_details',
        'status',
        'invoice_number',
        'invoice_date',
        'invoice_sequence_year',
        'invoice_sequence_month',
        'invoice_sequence_number',
        'subtotal',
        'discount_total',
        'vat_total',
        'additional_discount',
        'grand_total',
        'notes',
        'terms',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quote_date' => 'date',
            'valid_until' => 'date',
            'invoice_date' => 'date',
            'revision' => 'integer',
            'sequence_year' => 'integer',
            'sequence_month' => 'integer',
            'sequence_number' => 'integer',
            'invoice_sequence_year' => 'integer',
            'invoice_sequence_month' => 'integer',
            'invoice_sequence_number' => 'integer',
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'vat_total' => 'decimal:2',
            'additional_discount' => 'decimal:2',
            'grand_total' => 'decimal:2',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class)->orderBy('sort_order');
    }

    public function proformas(): HasMany
    {
        return $this->hasMany(ProformaInvoice::class)->orderBy('id');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class)->orderBy('id');
    }

    public function proformaPercentInvoiced(): float
    {
        if ($this->relationLoaded('proformas')) {
            return round((float) $this->proformas->sum('percent'), 2);
        }

        return round((float) $this->proformas()->sum('percent'), 2);
    }

    public function proformaRemainingPercent(): float
    {
        return round(max(0, 100 - $this->proformaPercentInvoiced()), 2);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function revisionLabel(): ?string
    {
        return $this->revision ? $this->revision.'R' : null;
    }

    public function displayNumber(): string
    {
        return $this->number;
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    public function ensureInvoiceNumber(?InvoiceNumberService $numbers = null): self
    {
        if ($this->invoice_number) {
            return $this;
        }

        $this->forceFill(($numbers ?? app(InvoiceNumberService::class))->allocate($this))->save();

        return $this;
    }

    public function netExclVat(): float
    {
        return round((float) $this->subtotal - (float) $this->discount_total - (float) $this->additional_discount, 2);
    }

    /**
     * @return list<array{heading: string, total: float, subs: list<array{title: string, items: \Illuminate\Support\Collection<int, QuotationItem>}>}>
     */
    public function groupedForPdf(): array
    {
        $groups = [];
        $carriedHeading = '';

        foreach ($this->items as $item) {
            $typed = trim((string) ($item->heading ?? ''));
            if ($typed !== '') {
                $carriedHeading = $typed;
            }
            $heading = $carriedHeading;
            $area = (string) $item->placing_area;
            $last = array_key_last($groups);

            if ($last === null || $groups[$last]['heading'] !== $heading) {
                $groups[] = [
                    'heading' => $heading,
                    'total' => 0.0,
                    'subs' => [['title' => $area, 'items' => collect()]],
                ];
                $last = array_key_last($groups);
            } else {
                $subLast = array_key_last($groups[$last]['subs']);
                if ($groups[$last]['subs'][$subLast]['title'] !== $area) {
                    $groups[$last]['subs'][] = ['title' => $area, 'items' => collect()];
                }
            }

            $subLast = array_key_last($groups[$last]['subs']);
            $groups[$last]['subs'][$subLast]['items']->push($item);
            $groups[$last]['total'] += (float) $item->line_total;
        }

        return $groups;
    }
}
