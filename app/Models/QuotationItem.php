<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationItem extends Model
{
    protected $fillable = [
        'quotation_id',
        'product_id',
        'heading',
        'placing_area',
        'image_path',
        'code',
        'description',
        'unit',
        'qty',
        'selling_price',
        'discount_percent',
        'vat_percent',
        'amount',
        'vat_amount',
        'line_total',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'decimal:3',
            'selling_price' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'vat_percent' => 'decimal:2',
            'amount' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function discountAmount(): float
    {
        return round(((float) $this->amount) * ((float) $this->discount_percent) / 100, 2);
    }

    public function netAmount(): float
    {
        return round(((float) $this->amount) - $this->discountAmount(), 2);
    }
}
