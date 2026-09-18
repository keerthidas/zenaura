<?php

namespace App\Services;

use App\Models\CompanySetting;
use App\Models\ProformaInvoice;
use App\Models\Quotation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ProformaNumberService
{
    public const DEFAULT_DESCRIPTION = ProformaInvoice::DEFAULT_DESCRIPTION;

    /**
     * @return array{number: string, invoice_date: string, sequence_year: int, sequence_month: int, sequence_number: int}
     */
    public function allocate(?Carbon $date = null): array
    {
        $date ??= now();
        $year = (int) $date->format('Y');
        $month = (int) $date->format('m');

        $max = ProformaInvoice::query()
            ->where('sequence_year', $year)
            ->where('sequence_month', $month)
            ->max('sequence_number');

        $seq = (int) $max + 1;

        return [
            'number' => $this->format($year, $month, $seq),
            'invoice_date' => $date->toDateString(),
            'sequence_year' => $year,
            'sequence_month' => $month,
            'sequence_number' => $seq,
        ];
    }

    public function format(int $year, int $month, int $seq): string
    {
        $prefix = CompanySetting::current()->invoice_prefix ?: 'ZS';

        return sprintf('%s/PI/%02d%02d/%d', $prefix, $month, $year % 100, $seq);
    }

    /**
     * @return array{subtotal: float, vat_total: float, grand_total: float}
     */
    public function amountsFor(Quotation $quotation, float $percent): array
    {
        $quotation->loadMissing('proformas');

        $quoteNet = $quotation->netExclVat();
        $quoteVat = (float) $quotation->vat_total;
        $quoteGrand = (float) $quotation->grand_total;
        $remaining = $quotation->proformaRemainingPercent();
        $isClosing = abs($percent - $remaining) < 0.001;

        if ($isClosing) {
            $subtotal = round($quoteNet - (float) $quotation->proformas->sum('subtotal'), 2);
            $vatTotal = round($quoteVat - (float) $quotation->proformas->sum('vat_total'), 2);
            $grandTotal = round($quoteGrand - (float) $quotation->proformas->sum('grand_total'), 2);
        } else {
            $factor = $percent / 100;
            $subtotal = round($quoteNet * $factor, 2);
            $grandTotal = round($quoteGrand * $factor, 2);
            $vatTotal = round($grandTotal - $subtotal, 2);
        }

        return [
            'subtotal' => $subtotal,
            'vat_total' => $vatTotal,
            'grand_total' => $grandTotal,
        ];
    }

    public function create(
        Quotation $quotation,
        float $percent,
        string $description,
        string $paymentTerms,
        string $deliveryTerms,
    ): ProformaInvoice {
        $remaining = $quotation->proformaRemainingPercent();

        if ($percent <= 0 || $percent > $remaining) {
            throw new \InvalidArgumentException('Proforma percent exceeds the remaining balance.');
        }

        $meta = $this->allocate();
        $amounts = $this->amountsFor($quotation, $percent);

        return ProformaInvoice::query()->create(array_merge($meta, $amounts, [
            'quotation_id' => $quotation->id,
            'percent' => $percent,
            'description' => $description,
            'payment_terms' => $paymentTerms,
            'delivery_terms' => $deliveryTerms,
            'created_by' => Auth::id(),
        ]));
    }
}
