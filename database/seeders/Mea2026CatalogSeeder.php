<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Seeds the Sanipex MEA 2026 retail catalogue (PDF pages 96-495).
 *
 * Source JSON/images: database/seeders/data/mea-2026
 * Re-extract: node scripts/extract-mea-catalog.mjs (requires pdfjs-dist)
 */
class Mea2026CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = database_path('seeders/data/mea-2026/products.json');

        if (! is_file($jsonPath)) {
            $this->command?->warn('MEA 2026 catalog JSON is missing. Skip Mea2026CatalogSeeder.');

            return;
        }

        $products = json_decode((string) file_get_contents($jsonPath), true, 512, JSON_THROW_ON_ERROR);
        $baseDir = database_path('seeders/data/mea-2026');
        $created = 0;
        $updated = 0;
        $images = 0;

        foreach ($products as $row) {
            $code = strtoupper(trim((string) ($row['code'] ?? '')));
            if ($code === '' || empty($row['description'])) {
                continue;
            }

            $product = Product::withTrashed()->updateOrCreate(
                ['code' => $code],
                [
                    'description' => $row['description'],
                    'actual_price' => $row['actual_price'] ?? 0,
                    'selling_price' => $row['selling_price'] ?? 0,
                    'unit' => $row['unit'] ?? 'pcs',
                    'is_active' => true,
                    'deleted_at' => null,
                ]
            );

            $product->wasRecentlyCreated ? $created++ : $updated++;

            $relative = $row['image'] ?? null;
            if (! is_string($relative) || $relative === '') {
                continue;
            }

            $source = $baseDir.DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative);
            if (! is_file($source)) {
                continue;
            }

            $dest = 'products/'.$product->id.'/catalog.png';
            $existing = $product->images()->where('path', $dest)->first();
            Storage::disk('public')->put($dest, (string) file_get_contents($source));

            if (! $existing) {
                $product->images()->create([
                    'path' => $dest,
                    'sort_order' => ($product->images()->max('sort_order') ?? 0) + 1,
                ]);
                $images++;
            }
        }

        $this->command?->info("MEA 2026 catalog: {$created} created, {$updated} updated, {$images} images attached.");
    }
}
