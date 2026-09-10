<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    protected static ?self $cached = null;

    protected $fillable = [
        'name',
        'address',
        'emirate',
        'phone',
        'email',
        'website',
        'trn',
        'logo_path',
        'quote_prefix',
        'invoice_prefix',
        'default_vat_percent',
        'default_validity_days',
        'default_unit',
        'currency',
        'default_terms',
        'bank_details',
    ];

    protected function casts(): array
    {
        return [
            'default_vat_percent' => 'decimal:2',
            'default_validity_days' => 'integer',
        ];
    }

    public static function current(): self
    {
        return static::$cached ??= static::query()->firstOrCreate(
            ['id' => 1],
            [
                'name' => 'Zenaura',
                'quote_prefix' => 'ZN',
                'invoice_prefix' => 'ZS',
                'default_vat_percent' => 5,
                'default_validity_days' => 30,
                'default_unit' => 'pcs',
                'currency' => 'AED',
                'default_terms' => "Prices are in AED and exclusive of VAT unless stated.\nThis quotation is valid for the period shown above.\nDelivery timeline to be confirmed upon order.\nThis is a quotation, not a tax invoice.",
            ]
        );
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::$cached = null);
    }
}
