<?php

namespace App\Services;

class QuotationCalculator
{
    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    public static function line(array $item): array
    {
        $qtyRaw = $item['qty'] ?? '';
        $qty = ($qtyRaw === '' || $qtyRaw === null) ? 1.0 : (float) $qtyRaw;
        $price = (float) ($item['selling_price'] ?? 0);
        $discount = (float) ($item['discount_percent'] ?? 0);
        $vat = (float) ($item['vat_percent'] ?? 0);

        $amount = round($qty * $price, 2);
        $discountValue = round($amount * ($discount / 100), 2);
        $net = round($amount - $discountValue, 2);
        $vatAmount = round($net * ($vat / 100), 2);
        $total = round($net + $vatAmount, 2);

        $item['qty'] = $qtyRaw === '' || $qtyRaw === null ? '' : $item['qty'];
        $item['amount'] = number_format($amount, 2, '.', '');
        $item['net'] = number_format($net, 2, '.', '');
        $item['unit_net'] = number_format(round($price * (1 - ($discount / 100)), 2), 2, '.', '');
        $item['vat_amount'] = number_format($vatAmount, 2, '.', '');
        $item['line_total'] = number_format($total, 2, '.', '');

        return $item;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array{subtotal: float, discount_total: float, vat_total: float, grand_total: float}
     */
    public static function totals(array $items): array
    {
        $subtotal = 0.0;
        $discountTotal = 0.0;
        $vatTotal = 0.0;
        $grand = 0.0;

        foreach ($items as $item) {
            $item = self::line($item);
            $amount = (float) $item['amount'];
            $discount = round($amount * ((float) ($item['discount_percent'] ?? 0) / 100), 2);

            $subtotal += $amount;
            $discountTotal += $discount;
            $vatTotal += (float) $item['vat_amount'];
            $grand += (float) $item['line_total'];
        }

        return [
            'subtotal' => round($subtotal, 2),
            'discount_total' => round($discountTotal, 2),
            'vat_total' => round($vatTotal, 2),
            'grand_total' => round($grand, 2),
        ];
    }
}
