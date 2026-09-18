<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProformaInvoice extends Model
{
    public const DEFAULT_DESCRIPTION = 'Supply of sanitary fittings including all necessary fixing materials as per approved specification and attached quantity details';

    protected $fillable = [
        'quotation_id',
        'number',
        'invoice_date',
        'sequence_year',
        'sequence_month',
        'sequence_number',
        'percent',
        'description',
        'payment_terms',
        'delivery_terms',
        'subtotal',
        'vat_total',
        'grand_total',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'sequence_year' => 'integer',
            'sequence_month' => 'integer',
            'sequence_number' => 'integer',
            'percent' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'vat_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
        ];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function receipt(): HasOne
    {
        return $this->hasOne(ReceiptVoucher::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function percentLabel(): string
    {
        return rtrim(rtrim(number_format((float) $this->percent, 2, '.', ''), '0'), '.').'%';
    }
}
