<?php

namespace App\Services;

use App\Models\CompanySetting;
use App\Models\ProformaInvoice;
use App\Models\ReceiptVoucher;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ReceiptNumberService
{
    /**
     * @return array{number: string, sequence_year: int, sequence_month: int, sequence_number: int}
     */
    public function allocate(?Carbon $date = null): array
    {
        $date ??= now();
        $year = (int) $date->format('Y');
        $month = (int) $date->format('m');

        $max = ReceiptVoucher::query()
            ->where('sequence_year', $year)
            ->where('sequence_month', $month)
            ->max('sequence_number');

        $seq = (int) $max + 1;

        return [
            'number' => $this->format($year, $month, $seq),
            'sequence_year' => $year,
            'sequence_month' => $month,
            'sequence_number' => $seq,
        ];
    }

    public function format(int $year, int $month, int $seq): string
    {
        $prefix = CompanySetting::current()->invoice_prefix ?: 'ZS';

        return sprintf('%s/RV/%02d%02d/%d', $prefix, $month, $year % 100, $seq);
    }

    public function create(
        ProformaInvoice $proforma,
        string $receivedDate,
        string $paymentMode,
        ?string $reference = null,
    ): ReceiptVoucher {
        $proforma->loadMissing('receipt');

        if ($proforma->receipt) {
            return $proforma->receipt;
        }

        $meta = $this->allocate(Carbon::parse($receivedDate));

        return ReceiptVoucher::query()->create(array_merge($meta, [
            'proforma_invoice_id' => $proforma->id,
            'received_date' => $receivedDate,
            'payment_mode' => $paymentMode,
            'reference' => $reference ?: null,
            'amount' => $proforma->grand_total,
            'created_by' => Auth::id(),
        ]));
    }
}
