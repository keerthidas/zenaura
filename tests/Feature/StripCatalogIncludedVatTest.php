<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\User;
use App\Services\QuotationCalculator;
use App\Support\Mea2026Catalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StripCatalogIncludedVatTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_converts_matching_catalogue_prices_once(): void
    {
        $prices = Mea2026Catalog::inclusivePricesByCode();
        $this->assertNotEmpty($prices);
        $this->assertArrayHasKey('BDM-CRL-301S-CP', $prices);

        $inclusive = $prices['BDM-CRL-301S-CP'];
        $exclusive = QuotationCalculator::exclusiveFromInclusive($inclusive, 5);

        $needsConvert = Product::query()->create([
            'code' => 'BDM-CRL-301S-CP',
            'description' => 'Mono Basin Mixer',
            'selling_price' => $inclusive,
            'actual_price' => 0,
            'unit' => 'pcs',
            'is_active' => true,
        ]);
        $alreadyExclusive = Product::query()->create([
            'code' => 'BDM-CRL-301S-NB',
            'description' => 'Mono Basin Mixer NB',
            'selling_price' => QuotationCalculator::exclusiveFromInclusive($prices['BDM-CRL-301S-NB'], 5),
            'actual_price' => 0,
            'unit' => 'pcs',
            'is_active' => true,
        ]);
        $manual = Product::query()->create([
            'code' => 'TAP-001',
            'description' => 'Manual product',
            'selling_price' => 145,
            'actual_price' => 85,
            'unit' => 'pcs',
            'is_active' => true,
        ]);

        $this->artisan('catalog:strip-included-vat')
            ->assertSuccessful()
            ->expectsOutputToContain('1 converted');

        $this->assertEquals($exclusive, (float) $needsConvert->fresh()->selling_price);
        $this->assertEquals((float) $alreadyExclusive->selling_price, (float) $alreadyExclusive->fresh()->selling_price);
        $this->assertEquals(145.00, (float) $manual->fresh()->selling_price);

        $this->artisan('catalog:strip-included-vat')
            ->assertSuccessful()
            ->expectsOutputToContain('already exclusive');

        $this->assertEquals($exclusive, (float) $needsConvert->fresh()->selling_price);
    }

    public function test_command_can_rewrite_draft_quotation_lines_only(): void
    {
        $this->seed();

        $inclusive = Mea2026Catalog::inclusivePricesByCode()['BDM-CRL-301S-CP'];
        $exclusive = QuotationCalculator::exclusiveFromInclusive($inclusive, 5);
        $product = Product::query()->create([
            'code' => 'BDM-CRL-301S-CP',
            'description' => 'Mono Basin Mixer',
            'selling_price' => $inclusive,
            'actual_price' => 0,
            'unit' => 'pcs',
            'is_active' => true,
        ]);

        $user = User::where('email', 'admin@zenaura.ae')->firstOrFail();
        $client = Client::firstOrFail();

        $draft = $this->makeQuote($client->id, $user->id, 'draft', $product, $inclusive);
        $sent = $this->makeQuote($client->id, $user->id, 'sent', $product, $inclusive);

        $this->artisan('catalog:strip-included-vat', ['--drafts' => true])->assertSuccessful();

        $draftItem = $draft->fresh('items')->items->first();
        $this->assertEquals($exclusive, (float) $draftItem->selling_price);
        $this->assertEquals($exclusive, (float) $draft->fresh()->subtotal);

        $sentItem = $sent->fresh('items')->items->first();
        $this->assertEquals($inclusive, (float) $sentItem->selling_price);
    }

    protected function makeQuote(int $clientId, int $userId, string $status, Product $product, float $price): Quotation
    {
        $line = QuotationCalculator::line([
            'qty' => 1,
            'selling_price' => $price,
            'discount_percent' => 0,
            'vat_percent' => 5,
        ]);
        $totals = QuotationCalculator::totals([$line]);

        $quote = Quotation::query()->create([
            'number' => 'ZN-TEST-'.$status.'-'.$product->id.'-'.uniqid(),
            'base_number' => 'ZN-TEST-'.$status,
            'client_id' => $clientId,
            'quote_date' => now(),
            'valid_until' => now()->addDays(30),
            'status' => $status,
            'created_by' => $userId,
            'subtotal' => $totals['subtotal'],
            'discount_total' => $totals['discount_total'],
            'vat_total' => $totals['vat_total'],
            'grand_total' => $totals['grand_total'],
        ]);

        $quote->items()->create([
            'product_id' => $product->id,
            'code' => $product->code,
            'description' => $product->description,
            'unit' => 'pcs',
            'qty' => 1,
            'selling_price' => $price,
            'discount_percent' => 0,
            'vat_percent' => 5,
            'amount' => $line['amount'],
            'vat_amount' => $line['vat_amount'],
            'line_total' => $line['line_total'],
            'sort_order' => 1,
        ]);

        return $quote;
    }
}
