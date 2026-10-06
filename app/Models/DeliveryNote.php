<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryNote extends Model
{
    protected $fillable = [
        'quotation_id',
        'supplier_id',
        'number',
        'delivery_date',
        'sequence_year',
        'sequence_month',
        'sequence_number',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'delivery_date' => 'date',
            'sequence_year' => 'integer',
            'sequence_month' => 'integer',
            'sequence_number' => 'integer',
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
        return $this->hasMany(DeliveryNoteItem::class)->orderBy('sort_order');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}