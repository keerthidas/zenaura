<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\User;
use App\Support\QuotationExcelExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Tests\TestCase;

class QuotationExcelExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_download_quotation_excel(): void
    {
        $user = User::factory()->create();
        $quotation = $this->makeQuotation();

        $response = $this->actingAs($user)->get(route('quotations.excel', $quotation));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('.xlsx', (string) $response->headers->get('content-disposition'));
    }

    public function test_excel_includes_line_items_and_totals(): void
    {
        $quotation = $this->makeQuotation();
        $sheet = (new QuotationExcelExporter)->build($quotation)->getActiveSheet();

        $this->assertSame('Quotation', $sheet->getCell('A1')->getValue());
        $this->assertSame($quotation->number, $this->valueBesideLabel($sheet, 'Quotation no.'));
        $this->assertTrue($this->sheetContains($sheet, 'TAP-100'));
        $this->assertTrue($this->sheetContains($sheet, 'CHROME TAP'));
        $this->assertTrue($this->sheetContains($sheet, 'Net Amount'));
        $this->assertTrue($this->sheetContains($sheet, 189.0));
    }

    public function test_hide_discount_excel_omits_discount_columns(): void
    {
        $user = User::factory()->create();
        $quotation = $this->makeQuotation();

        $response = $this->actingAs($user)->get(route('quotations.excel', [
            'quotation' => $quotation,
            'discount' => 'hide',
        ]));

        $response->assertOk();

        $tmp = tempnam(sys_get_temp_dir(), 'quote-xlsx-');
        try {
            file_put_contents($tmp, $response->streamedContent());
            $sheet = IOFactory::load($tmp)->getActiveSheet();

            $this->assertTrue($this->sheetContains($sheet, 'Sales Price'));
            $this->assertFalse($this->sheetContains($sheet, 'Disc %'));
            $this->assertFalse($this->sheetContains($sheet, 'Net sales price'));
        } finally {
            @unlink($tmp);
        }
    }

    private function makeQuotation(): Quotation
    {
        $client = Client::create([
            'name' => 'Excel Client',
            'trn' => '100000000000099',
            'phone' => '0500000000',
            'email' => 'client@example.com',
        ]);

        $quotation = Quotation::create([
            'number' => 'Q-2026-0001',
            'base_number' => 'Q-2026-0001',
            'client_id' => $client->id,
            'quote_date' => now()->toDateString(),
            'valid_until' => now()->addDays(14)->toDateString(),
            'status' => 'draft',
            'delivery' => 'Ex-stock',
            'payment_terms' => '30 days',
            'delivery_availability' => 'Immediate',
            'reference' => 'REF-1',
            'subtotal' => 200,
            'discount_total' => 20,
            'additional_discount' => 0,
            'vat_total' => 9,
            'grand_total' => 189,
            'created_by' => User::factory()->create()->id,
        ]);

        QuotationItem::create([
            'quotation_id' => $quotation->id,
            'heading' => 'Bathroom',
            'placing_area' => 'Ground floor',
            'code' => 'TAP-100',
            'description' => 'Chrome tap',
            'unit' => 'PCS',
            'qty' => 2,
            'selling_price' => 100,
            'discount_percent' => 10,
            'vat_percent' => 5,
            'amount' => 200,
            'vat_amount' => 9,
            'line_total' => 189,
            'sort_order' => 1,
        ]);

        return $quotation->fresh(['client', 'items']);
    }

    private function valueBesideLabel(Worksheet $sheet, string $label): mixed
    {
        foreach ($sheet->getRowIterator() as $row) {
            foreach ($row->getCellIterator('A', 'B') as $cell) {
                if ((string) $cell->getValue() === $label) {
                    return $sheet->getCell('B'.$cell->getRow())->getValue();
                }
            }
        }

        $this->fail("Label [{$label}] not found in spreadsheet.");
    }

    private function sheetContains(Worksheet $sheet, mixed $needle): bool
    {
        foreach ($sheet->getRowIterator() as $row) {
            foreach ($row->getCellIterator() as $cell) {
                $value = $cell->getValue();
                if (is_float($needle) || is_int($needle)) {
                    if (is_numeric($value) && abs((float) $value - (float) $needle) < 0.001) {
                        return true;
                    }

                    continue;
                }

                if ((string) $value === (string) $needle) {
                    return true;
                }
            }
        }

        return false;
    }
}
