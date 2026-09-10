<?php

namespace App\Services;

use App\Models\CompanySetting;
use App\Models\Quotation;
use Illuminate\Support\Carbon;

class InvoiceNumberService
{
    /**
     * @return array{invoice_number: string, invoice_date: string, invoice_sequence_year: int, invoice_sequence_month: int, invoice_sequence_number: int}
     */
    public function allocate(Quotation $quotation, ?Carbon $date = null): array
    {
        if ($quotation->invoice_number) {
            return [
                'invoice_number' => $quotation->invoice_number,
                'invoice_date' => optional($quotation->invoice_date)?->toDateString() ?: ($date ?? now())->toDateString(),
                'invoice_sequence_year' => (int) $quotation->invoice_sequence_year,
                'invoice_sequence_month' => (int) $quotation->invoice_sequence_month,
                'invoice_sequence_number' => (int) $quotation->invoice_sequence_number,
            ];
        }

        $date ??= now();
        $year = (int) $date->format('Y');
        $month = (int) $date->format('m');

        $max = Quotation::query()
            ->where('invoice_sequence_year', $year)
            ->where('invoice_sequence_month', $month)
            ->max('invoice_sequence_number');

        $seq = (int) $max + 1;

        return [
            'invoice_number' => $this->format($year, $month, $seq, $quotation->revision),
            'invoice_date' => $date->toDateString(),
            'invoice_sequence_year' => $year,
            'invoice_sequence_month' => $month,
            'invoice_sequence_number' => $seq,
        ];
    }

    public function format(int $year, int $month, int $seq, ?int $revision = null): string
    {
        $prefix = CompanySetting::current()->invoice_prefix ?: 'ZS';
        $number = sprintf('%s/TI/%02d%02d/%d', $prefix, $month, $year % 100, $seq);

        if ($revision) {
            return $number.'-'.$revision.'R';
        }

        return $number;
    }
}
