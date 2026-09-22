<?php

namespace App\Console\Commands;

use App\Models\CompanySetting;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Services\QuotationCalculator;
use App\Support\Mea2026Catalog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class StripCatalogIncludedVatCommand extends Command
{
    protected $signature = 'catalog:strip-included-vat
                            {--drafts : Also rewrite draft quotation lines that still have the inclusive catalogue price}';

    protected $description = 'Convert MEA 2026 catalogue selling prices from VAT-inclusive list prices to exclusive unit prices';

    public function handle(): int
    {
        $inclusiveByCode = Mea2026Catalog::inclusivePricesByCode();
        if ($inclusiveByCode === []) {
            $this->error('MEA 2026 catalogue JSON was not found.');

            return self::FAILURE;
        }

        $vat = (float) CompanySetting::current()->default_vat_percent;
        $updated = 0;
        $already = 0;
        $skipped = 0;

        Product::query()
            ->withTrashed()
            ->whereIn('code', array_keys($inclusiveByCode))
            ->orderBy('id')
            ->each(function (Product $product) use ($inclusiveByCode, $vat, &$updated, &$already, &$skipped): void {
                $code = strtoupper((string) $product->code);
                if (! isset($inclusiveByCode[$code])) {
                    $skipped++;

                    return;
                }

                $inclusive = $inclusiveByCode[$code];
                $exclusive = QuotationCalculator::exclusiveFromInclusive($inclusive, $vat);
                $current = (float) $product->selling_price;

                if (QuotationCalculator::pricesMatch($current, $exclusive)) {
                    $already++;

                    return;
                }

                if (! QuotationCalculator::pricesMatch($current, $inclusive)) {
                    $skipped++;

                    return;
                }

                $product->forceFill(['selling_price' => $exclusive])->save();
                $updated++;
            });

        $this->info("Products: {$updated} converted, {$already} already exclusive, {$skipped} left unchanged (price no longer matches the catalogue).");

        if ($this->option('drafts')) {
            $this->stripDraftLines($inclusiveByCode, $vat);
        } else {
            $this->comment('Draft quotations were not changed. Re-run with --drafts if those lines still show inclusive prices.');
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<string, float>  $inclusiveByCode
     */
    protected function stripDraftLines(array $inclusiveByCode, float $vat): void
    {
        $linesUpdated = 0;
        $quotesTouched = [];

        DB::transaction(function () use ($inclusiveByCode, $vat, &$linesUpdated, &$quotesTouched): void {
            $items = QuotationItem::query()
                ->whereHas('quotation', fn ($q) => $q->where('status', 'draft'))
                ->whereIn('code', array_keys($inclusiveByCode))
                ->get();

            foreach ($items as $item) {
                $inclusive = $inclusiveByCode[strtoupper((string) $item->code)] ?? null;
                if ($inclusive === null) {
                    continue;
                }

                $exclusive = QuotationCalculator::exclusiveFromInclusive($inclusive, $vat);
                $current = (float) $item->selling_price;

                if (QuotationCalculator::pricesMatch($current, $exclusive) || ! QuotationCalculator::pricesMatch($current, $inclusive)) {
                    continue;
                }

                $calc = QuotationCalculator::line([
                    'qty' => $item->qty,
                    'selling_price' => $exclusive,
                    'discount_percent' => $item->discount_percent,
                    'vat_percent' => $item->vat_percent,
                ]);

                $item->forceFill([
                    'selling_price' => $exclusive,
                    'amount' => $calc['amount'],
                    'vat_amount' => $calc['vat_amount'],
                    'line_total' => $calc['line_total'],
                ])->save();

                $linesUpdated++;
                $quotesTouched[$item->quotation_id] = true;
            }

            foreach (array_keys($quotesTouched) as $quotationId) {
                $quotation = Quotation::query()->with('items')->find($quotationId);
                if (! $quotation) {
                    continue;
                }

                $totals = QuotationCalculator::totals(
                    $quotation->items->map(fn (QuotationItem $item) => [
                        'qty' => $item->qty,
                        'selling_price' => $item->selling_price,
                        'discount_percent' => $item->discount_percent,
                        'vat_percent' => $item->vat_percent,
                    ])->all()
                );

                $quotation->forceFill($totals)->save();
            }
        });

        $this->info("Draft quotation lines: {$linesUpdated} converted across ".count($quotesTouched).' draft(s).');
    }
}
