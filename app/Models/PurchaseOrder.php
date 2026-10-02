<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use SoftDeletes;

    public const STATUS_ISSUED = 'issued';

    public const STATUS_CANCELLED = 'cancelled';

    public const DEFAULT_PAYMENT_TERMS = 'CASH IN ADVANCE';

    public const DEFAULT_DELIVERY_TERMS = 'DELIVERED AT PLACE/SITE';

    public const DEFAULT_DELIVERY_AVAILABILITY = 'EX STOCK';

    protected $fillable = [
        'quotation_id',
        'supplier_id',
        'number',
        'lpo_date',
        'sequence_year',
        'sequence_month',
        'sequence_number',
        'status',
        'supplier_quote_number',
        'supplier_quote_date',
        'customer_reference',
        'payment_terms',
        'delivery_terms',
        'delivery_availability',
        'buyer_details',
        'terms',
        'notes',
        'subtotal',
        'discount_total',
        'vat_total',
        'grand_total',
        'replaces_id',
        'cancelled_at',
        'cancelled_by',
        'cancel_reason',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'lpo_date' => 'date',
            'supplier_quote_date' => 'date',
            'cancelled_at' => 'datetime',
            'sequence_year' => 'integer',
            'sequence_month' => 'integer',
            'sequence_number' => 'integer',
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'vat_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
        ];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class)->withTrashed();
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class)->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class)->orderBy('sort_order');
    }

    public function replaces(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replaces_id');
    }

    public function replacement(): HasOne
    {
        return $this->hasOne(self::class, 'replaces_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function scopeIssued(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ISSUED);
    }

    public function isIssued(): bool
    {
        return $this->status === self::STATUS_ISSUED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function netExclVat(): float
    {
        return round((float) $this->subtotal - (float) $this->discount_total, 2);
    }

    public function vatLabel(): string
    {
        $rates = $this->items->map(fn (PurchaseOrderItem $item) => (float) $item->vat_percent)->unique()->values();

        if ($rates->count() !== 1) {
            return 'VAT';
        }

        return 'VAT '.rtrim(rtrim(number_format($rates->first(), 2, '.', ''), '0'), '.').'%';
    }

    public function quotationReference(): string
    {
        return trim((string) $this->supplier_quote_number) ?: (string) $this->quotation?->number;
    }

    public function quotationReferenceDate(): ?\Illuminate\Support\Carbon
    {
        return $this->supplier_quote_number ? $this->supplier_quote_date : $this->quotation?->quote_date;
    }

    /**
     * @return list<string>
     */
    public function termLines(): array
    {
        $custom = trim((string) $this->terms);

        if ($custom !== '') {
            return collect(preg_split('/\r\n|\r|\n/', $custom))
                ->map(fn ($line) => trim((string) $line))
                ->filter()
                ->values()
                ->all();
        }

        $itemWord = $this->items->count() === 1 ? 'item' : 'items';
        $lines = ['Supply the above '.$itemWord.' as per quotation '.$this->quotationReference().' and agreed specifications.'];

        if ($this->delivery_terms) {
            $lines[] = 'Delivery: '.$this->delivery_terms.'.';
        }
        if ($this->payment_terms) {
            $lines[] = 'Payment: '.$this->payment_terms.'.';
        }
        if ($this->delivery_availability) {
            $lines[] = 'Delivery availability: '.$this->delivery_availability.'.';
        }

        return $lines;
    }

    public static function defaultBuyerDetails(?CompanySetting $settings = null): string
    {
        $settings ??= CompanySetting::current();

        return collect([
            mb_strtoupper(trim((string) $settings->name)),
            trim((string) $settings->address),
            trim((string) $settings->emirate) !== '' ? trim((string) $settings->emirate).', UAE' : null,
            $settings->phone ? 'Tel: '.$settings->phone : null,
            $settings->email ? 'Email: '.$settings->email : null,
        ])->filter()->implode("\n");
    }
}
