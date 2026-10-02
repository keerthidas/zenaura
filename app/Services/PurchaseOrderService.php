<?php

namespace App\Services;

use App\Models\CompanySetting;
use App\Models\PurchaseOrder;
use App\Models\Quotation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService
{
    /**
     * @return array{number: string, sequence_year: int, sequence_month: int, sequence_number: int}
     */
    public function allocate(?Carbon $date = null): array
    {
        $date ??= now();
        $year = (int) $date->format('Y');
        $month = (int) $date->format('m');

        $seq = (int) PurchaseOrder::withTrashed()
            ->where('sequence_year', $year)
            ->where('sequence_month', $month)
            ->max('sequence_number') + 1;

        while (PurchaseOrder::withTrashed()->where('number', $this->format($year, $month, $seq))->exists()) {
            $seq++;
        }

        return [
            'number' => $this->format($year, $month, $seq),
            'sequence_year' => $year,
            'sequence_month' => $month,
            'sequence_number' => $seq,
        ];
    }

    public function preview(?Carbon $date = null): string
    {
        return $this->allocate($date)['number'];
    }

    public function format(int $year, int $month, int $seq): string
    {
        $prefix = CompanySetting::current()->quote_prefix ?: 'ZN';

        return sprintf('%s/PO/%02d%02d/%03d', $prefix, $month, $year % 100, $seq);
    }

    /**
     * Quantity per product code already placed on issued LPOs for this quotation.
     *
     * @return array<string, float>
     */
    public function orderedQuantities(Quotation $quotation, ?int $exceptPurchaseOrderId = null): array
    {
        $rows = DB::table('purchase_order_items')
            ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_items.purchase_order_id')
            ->where('purchase_orders.quotation_id', $quotation->id)
            ->where('purchase_orders.status', PurchaseOrder::STATUS_ISSUED)
            ->whereNull('purchase_orders.deleted_at')
            ->when($exceptPurchaseOrderId, fn ($q) => $q->where('purchase_orders.id', '!=', $exceptPurchaseOrderId))
            ->selectRaw('UPPER(purchase_order_items.code) as code_key, SUM(purchase_order_items.qty) as ordered')
            ->groupBy('code_key')
            ->get();

        return $rows->mapWithKeys(fn ($row) => [(string) $row->code_key => round((float) $row->ordered, 3)])->all();
    }

    /**
     * @param  array<string, mixed>  $line
     * @return array<string, mixed>
     */
    public static function calculateLine(array $line): array
    {
        $calculated = QuotationCalculator::line(array_merge($line, [
            'selling_price' => $line['unit_price'] ?? 0,
        ]));

        $amount = (float) $calculated['amount'];
        $net = (float) $calculated['net'];

        $line['amount'] = $calculated['amount'];
        $line['discount_amount'] = number_format(round($amount - $net, 2), 2, '.', '');
        $line['net_amount'] = $calculated['net'];
        $line['unit_net'] = $calculated['unit_net'];
        $line['vat_amount'] = $calculated['vat_amount'];
        $line['line_total'] = $calculated['line_total'];

        return $line;
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     * @return array{subtotal: float, discount_total: float, vat_total: float, grand_total: float}
     */
    public static function totals(array $lines): array
    {
        return QuotationCalculator::totals(array_map(
            fn (array $line) => array_merge($line, ['selling_price' => $line['unit_price'] ?? 0]),
            $lines,
        ));
    }

    /**
     * @param  array<string, mixed>  $header
     * @param  list<array<string, mixed>>  $lines
     */
    public function issue(Quotation $quotation, array $header, array $lines, ?PurchaseOrder $replacing = null): PurchaseOrder
    {
        if ($lines === []) {
            throw new \InvalidArgumentException('An LPO needs at least one line.');
        }

        return DB::transaction(function () use ($quotation, $header, $lines, $replacing) {
            if ($replacing) {
                $replacing = PurchaseOrder::query()->lockForUpdate()->findOrFail($replacing->id);

                if (! $replacing->isIssued() || $replacing->quotation_id !== $quotation->id) {
                    throw new \InvalidArgumentException('Only an active LPO of this quotation can be replaced.');
                }
            }

            $date = Carbon::parse($header['lpo_date'] ?? now());
            $totals = self::totals($lines);

            $order = PurchaseOrder::query()->create(array_merge($this->allocate($date), $totals, [
                'quotation_id' => $quotation->id,
                'supplier_id' => $header['supplier_id'],
                'lpo_date' => $date->toDateString(),
                'status' => PurchaseOrder::STATUS_ISSUED,
                'supplier_quote_number' => $header['supplier_quote_number'] ?? null,
                'supplier_quote_date' => $header['supplier_quote_date'] ?? null,
                'customer_reference' => $header['customer_reference'] ?? null,
                'payment_terms' => $header['payment_terms'] ?? null,
                'delivery_terms' => $header['delivery_terms'] ?? null,
                'delivery_availability' => $header['delivery_availability'] ?? null,
                'buyer_details' => $header['buyer_details'] ?? null,
                'terms' => $header['terms'] ?? null,
                'notes' => $header['notes'] ?? null,
                'replaces_id' => $replacing?->id,
                'created_by' => Auth::id(),
            ]));

            $sort = 0;
            foreach ($lines as $line) {
                $line = self::calculateLine($line);

                $order->items()->create([
                    'product_id' => $line['product_id'] ?? null,
                    'code' => strtoupper(trim((string) ($line['code'] ?? ''))) ?: null,
                    'description' => trim((string) $line['description']),
                    'unit' => strtoupper(trim((string) ($line['unit'] ?? ''))) ?: 'EACH',
                    'qty' => QuotationCalculator::number($line['qty'] ?? 0),
                    'quoted_price' => QuotationCalculator::number($line['quoted_price'] ?? 0),
                    'unit_price' => QuotationCalculator::number($line['unit_price'] ?? 0),
                    'discount_percent' => QuotationCalculator::number($line['discount_percent'] ?? 0),
                    'vat_percent' => QuotationCalculator::number($line['vat_percent'] ?? 0),
                    'amount' => $line['amount'],
                    'discount_amount' => $line['discount_amount'],
                    'net_amount' => $line['net_amount'],
                    'vat_amount' => $line['vat_amount'],
                    'line_total' => $line['line_total'],
                    'sort_order' => ++$sort,
                ]);
            }

            if ($replacing) {
                $this->markCancelled($replacing, 'Replaced by '.$order->number);
            }

            return $order;
        });
    }

    public function cancel(PurchaseOrder $order, ?string $reason = null): PurchaseOrder
    {
        return DB::transaction(function () use ($order, $reason) {
            $order = PurchaseOrder::query()->lockForUpdate()->findOrFail($order->id);

            if (! $order->isIssued()) {
                throw new \InvalidArgumentException('This LPO is already cancelled.');
            }

            $this->markCancelled($order, $reason);

            return $order;
        });
    }

    protected function markCancelled(PurchaseOrder $order, ?string $reason): void
    {
        $order->forceFill([
            'status' => PurchaseOrder::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancelled_by' => Auth::id(),
            'cancel_reason' => $reason !== null && trim($reason) !== '' ? trim($reason) : null,
        ])->save();
    }
}
