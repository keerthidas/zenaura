<?php

namespace App\Support;

class Mea2026Catalog
{
    public static function path(): string
    {
        return database_path('seeders/data/mea-2026/products.json');
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function rows(): array
    {
        $path = static::path();
        if (! is_file($path)) {
            return [];
        }

        $rows = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        return is_array($rows) ? $rows : [];
    }

    /**
     * VAT-inclusive list prices keyed by product code, as printed in the PDF.
     *
     * @return array<string, float>
     */
    public static function inclusivePricesByCode(): array
    {
        $map = [];

        foreach (static::rows() as $row) {
            $code = strtoupper(trim((string) ($row['code'] ?? '')));
            if ($code === '') {
                continue;
            }

            $map[$code] = (float) ($row['selling_price'] ?? 0);
        }

        return $map;
    }
}
