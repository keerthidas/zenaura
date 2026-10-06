<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryNoteItem extends Model
{
    protected $fillable = [
        'delivery_note_id',
        'quotation_item_id',
        'product_id',
        'code',
        'description',
        'unit',
        'quoted_qty',
        'delivered_before',
        'delivered_qty',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'quoted_qty' => 'decimal:3',
            'delivered_before' => 'decimal:3',
            'delivered_qty' => 'decimal:3',
        ];
    }

    public function deliveryNote(): BelongsTo
    {
        return $this->belongsTo(DeliveryNote::class);
    }

    public function quotationItem(): BelongsTo
    {
        return $this->belongsTo(QuotationItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
}