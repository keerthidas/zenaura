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
            $this->appendPages($merged, $brandBoardPath, true);
            $this->appendPages($merged, $quotationPath);

            return $merged->Output('S');
        } finally {
            unlink($quotationPath);
        }
    }

    private function appendPages(
        Fpdi $merged,
        string $path,
        bool $addWebsiteLink = false,
    ): void {
        $pageCount = $merged->setSourceFile($path);

        for ($page = 1; $page <= $pageCount; $page++) {
            $template = $merged->importPage($page);
            $size = $merged->getTemplateSize($template);

            $merged->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $merged->useTemplate($template);

            if ($addWebsiteLink) {
                $merged->Link(
                    $size['width'] * 0.30,
                    $size['height'] * 0.965,
                    $size['width'] * 0.40,
                    $size['height'] * 0.026,
                    'https://www.zenaurasanitary.com/',
                );
            }
        }
    }
}
