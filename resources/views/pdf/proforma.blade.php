<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $proforma->number }}</title>
    <style>
        @page { margin: 72px 36px 56px 36px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
        table { width: 100%; border-collapse: collapse; }
        table, th, td { font-size: 10px; }
        .right { text-align: right; }
        .center { text-align: center; }
        .pdf-header { position: fixed; top: -60px; left: 0; right: 0; }
        .pdf-header td { vertical-align: top; }
        .logo { max-height: 52px; max-width: 220px; }
        .co-contact { font-size: 10px; line-height: 1.45; color: #222; text-align: right; }
        .doc-title { font-size: 20px; font-weight: bold; text-align: center; letter-spacing: 1px; margin: 0 0 14px; }
        .pdf-footer { position: fixed; bottom: -42px; left: 0; right: 0; height: 32px; border-top: 0.8px solid #bbb; padding-top: 6px; font-size: 9px; color: #444; letter-spacing: 1.4px; }
        .page-num:after { content: counter(page); }
        .meta { margin-bottom: 12px; }
        .meta td { vertical-align: top; padding: 3px 0; }
        .meta .k { color: #333; width: 18%; }
        .meta .v { font-weight: bold; width: 32%; padding-right: 10px; }
        .items th { border-bottom: 0.9px solid #111; text-align: left; padding: 6px 4px; font-size: 10px; }
        .items td { border-bottom: 0.4px solid #ccc; vertical-align: top; padding: 8px 4px; }
        .items .num { width: 8%; }
        .items .qty { width: 22%; }
        .items .price { width: 14%; }
        .items .tot { width: 16%; }
        .totals { margin-top: 8px; }
        .totals td { padding: 4px 6px; }
        .totals .lab { text-align: right; }
        .final td { font-weight: bold; font-size: 11px; }
        .split { margin-top: 18px; }
        .split td { border: 0.8px solid #111; padding: 8px 10px; }
        .split .cap { font-weight: bold; }
        img { border: 0; }
    </style>
</head>
<body>
    @php
        $logoDataUri = $logoDataUri ?? null;
        $client = $quotation->client;
        $currency = $settings->currency ?: 'AED';
        $lhPhone = trim((string) $settings->phone);
        $lhEmail = trim((string) $settings->email);
        $lhAddress = collect([$settings->address, $settings->emirate])->filter()->implode(', ');
        $po = trim((string) $quotation->reference) ?: '000';
        $remaining = round(max(0, 100 - (float) $quotation->proformas->where('id', '<=', $proforma->id)->sum('percent')), 2);
        $quoteGrand = (float) $quotation->grand_total;
        $balanceAmount = round($quoteGrand * ($remaining / 100), 2);
        $percentLabel = $proforma->percentLabel();
        $remainingLabel = rtrim(rtrim(number_format($remaining, 2, '.', ''), '0'), '.').'%';
        $paymentTerms = trim((string) $quotation->payment_terms);
        if ($paymentTerms === '') {
            $paymentTerms = $remaining > 0
                ? $percentLabel.' Advance, '.$remainingLabel.' Before Delivery'
                : $percentLabel.' Balance';
        }
        $deliveryTerms = trim((string) ($quotation->delivery_availability ?: $quotation->delivery)) ?: 'TO BE CONFIRMED';
        $customerAddress = collect([$client?->address, $client?->emirate, $client?->emirate ? 'UAE' : null])->filter()->implode(', ');
        $trn = trim((string) ($client?->trn ?: '')) ?: 'NA';
        $qtyNote = 'As per attached details and quantities in QTN '.$quotation->number;
        $companyLine = strtoupper(trim((string) $settings->name));
    @endphp

    <div class="pdf-header">
        <table>
            <tr>
                <td width="55%">
                    @if (! empty($logoDataUri))
                        <img src="{{ $logoDataUri }}" class="logo">
                    @else
                        <div style="font-size: 18px; font-weight: bold;">ZENAURA SANITARY</div>
                    @endif
                </td>
                <td width="45%" class="co-contact">
                    @if ($lhPhone) {{ $lhPhone }}<br> @endif
                    @if ($lhEmail) {{ $lhEmail }}<br> @endif
                    @if ($lhAddress) {{ $lhAddress }} @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="pdf-footer">
        <table>
            <tr>
                <td width="78%">{{ $companyLine }}</td>
                <td width="22%" class="right">Page <span class="page-num"></span></td>
            </tr>
        </table>
    </div>

    <div class="doc-title">PROFORMA INVOICE</div>

    <table class="meta">
        <tr>
            <td class="k">Invoice No:</td>
            <td class="v">{{ $proforma->number }}</td>
            <td class="k">Invoice Date:</td>
            <td class="v">{{ $proforma->invoice_date->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td class="k">PO No:</td>
            <td class="v">{{ $po }}</td>
            <td class="k">Payment Terms:</td>
            <td class="v">{{ $paymentTerms }}</td>
        </tr>
        <tr>
            <td class="k">Customer Name:</td>
            <td class="v">{{ $client?->name }}</td>
            <td class="k">Delivery Terms:</td>
            <td class="v">{{ $deliveryTerms }}</td>
        </tr>
        <tr>
            <td class="k">Customer Address:</td>
            <td class="v" colspan="3">{{ $customerAddress ?: 'NA' }}</td>
        </tr>
        <tr>
            <td class="k">Customer TRN:</td>
            <td class="v" colspan="3">{{ $trn }}</td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th class="num">S.No</th>
                <th>Description</th>
                <th class="qty">Qty</th>
                <th class="price right">Unit Price</th>
                <th class="tot right">Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1</td>
                <td>{{ $proforma->description }}</td>
                <td>{{ $qtyNote }}</td>
                <td class="right"></td>
                <td class="right">{{ number_format((float) $proforma->subtotal, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td width="68%"></td>
            <td class="lab" width="16%">VAT (AED)</td>
            <td class="right" width="16%">{{ number_format((float) $proforma->vat_total, 2) }}</td>
        </tr>
        <tr class="final">
            <td></td>
            <td class="lab">FINAL AMOUNT</td>
            <td class="right">{{ number_format((float) $proforma->grand_total, 2) }}</td>
        </tr>
    </table>

    <table class="split">
        <tr>
            <td width="50%">
                <div class="cap">{{ $percentLabel }} Advance to be Paid</div>
                <div>{{ $currency }} {{ number_format((float) $proforma->grand_total, 2) }}</div>
            </td>
            <td width="50%">
                <div class="cap">{{ $remaining > 0 ? $remainingLabel.' Balance to be Paid' : 'Balance Paid' }}</div>
                <div>{{ $currency }} {{ number_format($balanceAmount, 2) }}</div>
            </td>
        </tr>
    </table>
</body>
</html>
