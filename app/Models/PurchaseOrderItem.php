<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'code',
        'description',
        'unit',
        'qty',
        'quoted_price',
        'unit_price',
        'discount_percent',
        'vat_percent',
        'amount',
        'discount_amount',
        'net_amount',
        'vat_amount',
        'line_total',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'decimal:3',
            'quoted_price' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'vat_percent' => 'decimal:2',
            'amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function netUnitPrice(): float
    {
        return round((float) $this->unit_price * (1 - ((float) $this->discount_percent / 100)), 2);
    }
}
