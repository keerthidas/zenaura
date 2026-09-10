<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'description',
        'actual_price',
        'selling_price',
        'unit',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'actual_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function quotationItems(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }
}
