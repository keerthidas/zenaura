<?php

namespace App\Support;

use RuntimeException;
use setasign\Fpdi\Fpdi;

class QuotationPdfMerger
{
    public function append(string $quotationPdf, string $brandBoardPath): string
    {
        if (! is_readable($brandBoardPath)) {
            throw new RuntimeException('The quotation brand-board PDF is missing or unreadable.');
        }

        $quotationPath = tempnam(sys_get_temp_dir(), 'zenaura-quotation-');
        if ($quotationPath === false) {
            throw new RuntimeException('Unable to create a temporary quotation PDF.');
        }

        try {
            if (file_put_contents($quotationPath, $quotationPdf) !== strlen($quotationPdf)) {
                throw new RuntimeException('Unable to write the generated quotation PDF.');
            }

            $merged = new Fpdi;
            $this->appendPages($merged, $quotationPath);
            $this->appendPages($merged, $brandBoardPath, true, true);

            return $merged->Output('S');
        } finally {
            unlink($quotationPath);
        }
    }

    private function appendPages(
        Fpdi $merged,
        string $path,
        bool $addWebsiteLink = false,
        bool $fitOnA4 = false,
    ): void {
        $pageCount = $merged->setSourceFile($path);

        for ($page = 1; $page <= $pageCount; $page++) {
            $template = $merged->importPage($page);
            $size = $merged->getTemplateSize($template);

            if ($fitOnA4) {
                $pageHeight = 297.0;
                $scale = $pageHeight / $size['height'];
                $pageWidth = $size['width'] * $scale;
                $left = (210.0 - $pageWidth) / 2;

                $merged->AddPage('P', 'A4');
                $merged->SetFillColor(250, 248, 243);
                $merged->Rect(0, 0, 210, $pageHeight, 'F');
                $merged->useTemplate($template, $left, 0, $pageWidth, $pageHeight);
            } else {
                $merged->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $merged->useTemplate($template);
            }

            if ($addWebsiteLink) {
                $linkScale = $fitOnA4 ? $pageHeight / $size['height'] : 1;
                $linkLeft = $fitOnA4 ? $left : 0;
                $linkWidth = $size['width'] * $linkScale;
                $linkHeight = $size['height'] * $linkScale;

                $merged->Link(
                    $linkLeft + $linkWidth * 0.30,
                    $linkHeight * 0.965,
                    $linkWidth * 0.40,
                    $linkHeight * 0.026,
                    'https://www.zenaurasanitary.com/',
                );
            }
        }
    }
}
