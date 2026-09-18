<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceiptVoucher extends Model
{
    public const PAYMENT_MODES = ['Bank Transfer', 'Cash', 'Cheque'];

    protected $fillable = [
        'proforma_invoice_id',
        'number',
        'received_date',
        'sequence_year',
        'sequence_month',
        'sequence_number',
        'payment_mode',
        'reference',
        'amount',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'received_date' => 'date',
            'sequence_year' => 'integer',
            'sequence_month' => 'integer',
            'sequence_number' => 'integer',
            'amount' => 'decimal:2',
        ];
    }

    public function proforma(): BelongsTo
    {
        return $this->belongsTo(ProformaInvoice::class, 'proforma_invoice_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
