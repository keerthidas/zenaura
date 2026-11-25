<?php

namespace App\Support;

use App\Models\Quotation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class QuotationExcelExporter
{
    public function download(Quotation $quotation, bool $hideDiscount = false): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $quotation->loadMissing(['client', 'items']);

        $spreadsheet = $this->build($quotation, $hideDiscount);
        $filename = str_replace(['/', '\\'], '-', $quotation->number).'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function build(Quotation $quotation, bool $hideDiscount = false): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Quotation');

        $client = $quotation->client;
        $row = 1;

        $sheet->setCellValue("A{$row}", 'Quotation');
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(14);
        $row += 2;

        $meta = [
            ['Quotation no.', $quotation->number],
            ['Revision', $quotation->revisionLabel() ?: '—'],
            ['Date', optional($quotation->quote_date)->format('d/m/Y')],
            ['Validity', optional($quotation->valid_until)->format('d/m/Y')],
            ['Status', $quotation->status],
            ['Customer', $client?->name],
            ['Customer phone', $client?->phone],
            ['Customer email', $client?->email],
            ['Customer VAT no.', $client?->trn],
            ['Customer ref.', $quotation->reference],
            ['Delivery', $quotation->delivery],
            ['Payment terms', $quotation->payment_terms],
            ['Availability', $quotation->delivery_availability],
            ['Salesperson', $quotation->salesperson],
            ['Sales admin', $quotation->sales_admin],
        ];

        foreach ($meta as [$label, $value]) {
            $sheet->setCellValue("A{$row}", $label);
            $sheet->setCellValue("B{$row}", $value);
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            $row++;
        }

        if (filled($quotation->delivery_details)) {
            $row++;
            $sheet->setCellValue("A{$row}", 'Delivery details');
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            $row++;
            $sheet->setCellValue("A{$row}", $quotation->delivery_details);
            $sheet->mergeCells("A{$row}:F{$row}");
            $sheet->getStyle("A{$row}")->getAlignment()->setWrapText(true);
            $row++;
        }

        $row += 2;
        $headers = $hideDiscount
            ? ['Heading', 'Area', 'Article', 'Description', 'Unit', 'Qty', 'Sales Price', 'Value (Ex. VAT)', 'VAT Value', 'Value (Inc. VAT)']
            : ['Heading', 'Area', 'Article', 'Description', 'Unit', 'Qty', 'Sales Price', 'Disc %', 'Net sales price', 'Value (Ex. VAT)', 'VAT Value', 'Value (Inc. VAT)'];

        $headerRow = $row;
        foreach ($headers as $index => $header) {
            $sheet->setCellValue([$index + 1, $row], $header);
        }
        $sheet->getStyle("A{$row}:".chr(64 + count($headers))."{$row}")->getFont()->setBold(true);
        $row++;

        $carriedHeading = '';
        foreach ($quotation->items as $item) {
            $typed = trim((string) ($item->heading ?? ''));
            if ($typed !== '') {
                $carriedHeading = $typed;
            }

            $discountPercent = (float) $item->discount_percent;
            $netSalesPrice = round((float) $item->selling_price * (1 - ($discountPercent / 100)), 2);
            $salesPrice = $hideDiscount ? $netSalesPrice : (float) $item->selling_price;
            $net = round(((float) $item->amount) * (1 - ($discountPercent / 100)), 2);

            $values = [
                $carriedHeading,
                (string) $item->placing_area,
                mb_strtoupper((string) $item->code),
                mb_strtoupper((string) $item->description),
                strtoupper((string) $item->unit),
                (float) $item->qty,
                $salesPrice,
            ];

            if (! $hideDiscount) {
                $values[] = $discountPercent;
                $values[] = $netSalesPrice;
            }

            $values[] = $net;
            $values[] = (float) $item->vat_amount;
            $values[] = (float) $item->line_total;

            foreach ($values as $index => $value) {
                $sheet->setCellValue([$index + 1, $row], $value);
            }
            $row++;
        }

        $row++;
        $labelCol = $hideDiscount ? 'G' : 'I';
        $valueCol = $hideDiscount ? 'J' : 'L';

        $gross = $hideDiscount
            ? round((float) $quotation->subtotal - (float) $quotation->discount_total, 2)
            : (float) $quotation->subtotal;

        $totals = [
            ['Gross amount', $gross],
        ];

        if (! $hideDiscount) {
            $totals[] = ['Discount', (float) $quotation->discount_total];
        }

        $totals[] = ['Total amount (Excl. VAT)', round((float) $quotation->subtotal - (float) $quotation->discount_total, 2)];

        if ((float) $quotation->additional_discount > 0) {
            $totals[] = ['Additional discount', (float) $quotation->additional_discount];
        }

        $totals[] = ['VAT Amount', (float) $quotation->vat_total];
        $totals[] = ['Net Amount', (float) $quotation->grand_total];

        foreach ($totals as [$label, $amount]) {
            $sheet->setCellValue("{$labelCol}{$row}", $label);
            $sheet->setCellValue("{$valueCol}{$row}", $amount);
            $sheet->getStyle("{$labelCol}{$row}")->getFont()->setBold(true);
            if ($label === 'Net Amount') {
                $sheet->getStyle("{$labelCol}{$row}:{$valueCol}{$row}")->getFont()->setBold(true);
            }
            $row++;
        }

        if (filled($quotation->terms)) {
            $row++;
            $sheet->setCellValue("A{$row}", 'Terms');
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            $row++;
            $sheet->setCellValue("A{$row}", $quotation->terms);
            $sheet->mergeCells("A{$row}:F{$row}");
            $sheet->getStyle("A{$row}")->getAlignment()->setWrapText(true);
        }

        $qtyCol = 'F';
        $moneyStart = 'G';
        $moneyEnd = $hideDiscount ? 'J' : 'L';
        $lastDataRow = max($headerRow + 1, $row);

        $sheet->getStyle("{$qtyCol}".($headerRow + 1).":{$qtyCol}{$lastDataRow}")
            ->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
        $sheet->getStyle("{$moneyStart}".($headerRow + 1).":{$moneyEnd}{$lastDataRow}")
            ->getNumberFormat()->setFormatCode('#,##0.00');

        foreach (range(1, count($headers)) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }

        $sheet->freezePane('A'.($headerRow + 1));
        $sheet->getStyle("A{$headerRow}:".chr(64 + count($headers))."{$headerRow}")
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        return $spreadsheet;
    }
}
