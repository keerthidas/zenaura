<?php

namespace Tests\Unit;

use App\Support\QuotationPdfMerger;
use PHPUnit\Framework\TestCase;
use setasign\Fpdi\Fpdi;

class QuotationPdfMergerTest extends TestCase
{
    public function test_it_appends_the_brand_board_and_keeps_its_website_link(): void
    {
        $quotationPath = tempnam(sys_get_temp_dir(), 'quotation-test-');
        $mergedPath = tempnam(sys_get_temp_dir(), 'merged-quotation-test-');
        $brandBoardPath = dirname(__DIR__, 2).'/resources/pdf/brand-board.pdf';

        try {
            file_put_contents($quotationPath, $this->blankPdf(210, 297));

            $merged = (new QuotationPdfMerger)->append(
                (string) file_get_contents($quotationPath),
                $brandBoardPath,
            );
            file_put_contents($mergedPath, $merged);

            $reader = new Fpdi;
            $this->assertSame(2, $reader->setSourceFile($mergedPath));
            $brandPage = $reader->importPage(2);
            $size = $reader->getTemplateSize($brandPage);

            $this->assertEqualsWithDelta(210.0, $size['width'], 0.1);
            $this->assertEqualsWithDelta(297.0, $size['height'], 0.1);
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
