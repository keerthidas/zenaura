<?php

namespace Tests\Unit;

use App\Support\QuotationPdfMerger;
use PHPUnit\Framework\TestCase;
use setasign\Fpdi\Fpdi;

class QuotationPdfMergerTest extends TestCase
{
    public function test_it_places_the_original_brand_board_first_and_keeps_its_website_link(): void
    {
        $quotationPath = tempnam(sys_get_temp_dir(), 'quotation-test-');
        $mergedPath = tempnam(sys_get_temp_dir(), 'merged-quotation-test-');
        $brandBoardPath = dirname(__DIR__, 2).'/resources/pdf/brand-board.pdf';

        try {
            file_put_contents($quotationPath, $this->blankPdf(200, 280));

            $merged = (new QuotationPdfMerger)->append(
                (string) file_get_contents($quotationPath),
                $brandBoardPath,
            );
            file_put_contents($mergedPath, $merged);

            $brandReader = new Fpdi;
            $brandReader->setSourceFile($brandBoardPath);
            $sourceBrandPage = $brandReader->importPage(1);
            $sourceBrandSize = $brandReader->getTemplateSize($sourceBrandPage);

            $reader = new Fpdi;
            $this->assertSame(2, $reader->setSourceFile($mergedPath));
            $brandPage = $reader->importPage(1);
            $brandSize = $reader->getTemplateSize($brandPage);
            $this->assertEqualsWithDelta($sourceBrandSize['width'], $brandSize['width'], 0.1);
            $this->assertEqualsWithDelta($sourceBrandSize['height'], $brandSize['height'], 0.1);

            $quotationPage = $reader->importPage(2);
            $quotationSize = $reader->getTemplateSize($quotationPage);
            $this->assertEqualsWithDelta(200.0, $quotationSize['width'], 0.1);
            $this->assertEqualsWithDelta(280.0, $quotationSize['height'], 0.1);
            $this->assertStringContainsString('https://www.zenaurasanitary.com/', $merged);
        } finally {
            unlink($quotationPath);
            unlink($mergedPath);
        }
    }

    private function blankPdf(float $width, float $height): string
    {
        $pdf = new Fpdi;
        $pdf->AddPage('P', [$width, $height]);

        return $pdf->Output('S');
    }
}
