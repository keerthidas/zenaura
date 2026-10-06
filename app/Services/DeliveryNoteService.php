<?php

namespace App\Services;

use App\Models\CompanySetting;
use App\Models\DeliveryNote;
use App\Models\DeliveryNoteItem;
use App\Models\Quotation;
use App\Models\Supplier;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DeliveryNoteService
{
    /**
     * @return array{number: string, sequence_year: int, sequence_month: int, sequence_number: int}
     */
    public function allocate(?Carbon $date = null): array
    {
        $date ??= now();
        $year = (int) $date->format('Y');
        $month = (int) $date->format('m');
        $sequence = (int) DeliveryNote::query()
            ->where('sequence_year', $year)
            ->where('sequence_month', $month)
            ->max('sequence_number') + 1;

        while (DeliveryNote::query()->where('number', $this->format($year, $month, $sequence))->exists()) {
            $sequence++;
        }

        return [
            'number' => $this->format($year, $month, $sequence),
            'sequence_year' => $year,
            'sequence_month' => $month,
            'sequence_number' => $sequence,
        ];
    }

    public function format(int $year, int $month, int $sequence): string
    {
        $prefix = CompanySetting::current()->quote_prefix ?: 'ZN';

        return sprintf('%s/DN/%02d%02d/%03d', $prefix, $month, $year % 100, $sequence);
    }

    /**
     * @return array<int, float>
     */
    public function deliveredQuantities(Quotation $quotation): array
    {
        return DeliveryNoteItem::query()
            ->whereNotNull('quotation_item_id')
            ->whereHas('deliveryNote', fn ($query) => $query->where('quotation_id', $quotation->id))
            ->selectRaw('quotation_item_id, SUM(delivered_qty) as delivered')
            ->groupBy('quotation_item_id')
            ->get()
            ->mapWithKeys(fn ($row) => [(int) $row->quotation_item_id => round((float) $row->delivered, 3)])
            ->all();
    }

    /**
     * @param  list<array{quotation_item_id: int, delivered_qty: float}>  $lines
     */
    public function create(Quotation $quotation, Supplier $supplier, string $date, array $lines, ?string $notes = null): DeliveryNote
    {
        return DB::transaction(function () use ($quotation, $supplier, $date, $lines, $notes): DeliveryNote {
            $quotation = Quotation::query()
                ->with('items')
                ->where('status', 'accepted')
                ->lockForUpdate()
                ->findOrFail($quotation->id);
            $delivered = $this->deliveredQuantities($quotation);
            $deliveryDate = Carbon::parse($date);
            $note = DeliveryNote::query()->create(array_merge($this->allocate($deliveryDate), [
                'quotation_id' => $quotation->id,
                'supplier_id' => $supplier->id,
                'delivery_date' => $deliveryDate->toDateString(),
                'notes' => $notes,
                'created_by' => Auth::id(),
            ]));

            foreach ($lines as $index => $line) {
                $quotationItem = $quotation->items->firstWhere('id', (int) $line['quotation_item_id']);
                $quantity = round((float) $line['delivered_qty'], 3);

                if (! $quotationItem || $quantity <= 0) {
                    throw new \InvalidArgumentException('Delivery note lines must refer to quotation items and have positive quantities.');
                }

                $note->items()->create([
                    'quotation_item_id' => $quotationItem->id,
                    'product_id' => $quotationItem->product_id,
                    'code' => $quotationItem->code,
                    'description' => $quotationItem->description,
                    'unit' => $quotationItem->unit,
                    'quoted_qty' => $quotationItem->qty,
                    'delivered_before' => $delivered[$quotationItem->id] ?? 0,
                    'delivered_qty' => $quantity,
                    'sort_order' => $index + 1,
                ]);

                $delivered[$quotationItem->id] = ($delivered[$quotationItem->id] ?? 0) + $quantity;
            }

            return $note->load(['quotation.client', 'supplier', 'items']);
        });
    }
}