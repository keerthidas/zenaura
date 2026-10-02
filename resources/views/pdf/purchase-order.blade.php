<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $order->number }}</title>
    <style>
        @page { margin: 32px 34px 70px 34px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
        table, th, td { font-size: 10px; }
        table { width: 100%; border-collapse: collapse; }
        .right { text-align: right; }
        .center { text-align: center; }
        .header td { vertical-align: top; }
        .logo { max-height: 56px; max-width: 220px; }
        .co-contact { font-size: 10px; line-height: 1.5; color: #222; text-align: right; }
        .doc-title { font-size: 20px; font-weight: bold; text-align: center; margin: 10px 0 12px; letter-spacing: 0.5px; }
        .cancelled { border: 1.5px solid #b91c1c; color: #b91c1c; text-align: center; font-weight: bold; font-size: 12px; padding: 5px; margin-bottom: 10px; letter-spacing: 1px; }
        .meta { margin-bottom: 10px; }
        .meta td { border: 0.8px solid #000; padding: 4px 6px; vertical-align: top; }
        .meta .k { background: #e6e6e6; width: 19%; }
        .meta .v { width: 31%; font-weight: bold; }
        .parties { margin-bottom: 10px; }
        .parties td.col { vertical-align: top; }
        .box { border: 0.8px solid #000; }
        .box .cap { background: #0e3d44; color: #fff; padding: 4px 6px; font-weight: bold; letter-spacing: 0.08em; }
        .box .body { padding: 6px; line-height: 1.45; min-height: 78px; }
        .items th { background: #0e3d44; color: #fff; font-weight: bold; padding: 5px 4px 6px; vertical-align: bottom; text-align: left; }
        .items th.right { text-align: right; }
        .items td { padding: 6px 4px; border-bottom: 0.4px solid #cfcfcf; vertical-align: top; }
        .items thead { display: table-header-group; }
        .items tbody tr { page-break-inside: avoid; }
        .sum-wrap { margin-top: 12px; page-break-inside: avoid; }
        .sum { border: 1.2px solid #000; }
        .sum td { border: 0.6px solid #000; padding: 4px 6px; }
        .sum .lab { background: #e6e6e6; width: 56%; }
        .sum .cur { width: 12%; }
        .sum .net td { font-weight: bold; font-size: 11px; }
        .terms { margin-top: 14px; line-height: 1.55; page-break-inside: avoid; }
        .terms h4 { font-size: 11px; margin: 0 0 4px; }
        .signs { margin-top: 30px; page-break-inside: avoid; }
        .signs td { vertical-align: bottom; padding: 0 10px; }
        .sign-title { font-weight: bold; margin-bottom: 6px; }
        .sign-media { width: 100%; margin-bottom: 4px; }
        .sign-media td { vertical-align: bottom; padding: 0; }
        .sign-img { height: 56px; max-width: 160px; }
        .stamp-img { height: 96px; }
        .sign-label { border-top: 0.9px solid #111; padding-top: 5px; }
        .doc-footer { position: fixed; left: 0; right: 0; bottom: -56px; height: 40px; border-top: 0.8px solid #bbb; padding-top: 6px; color: #444; }
        .page-num:after { content: counter(page); }
        img { border: 0; }
    </style>
</head>
<body>
    @php
        $supplier = $order->supplier;
        $currency = $settings->currency ?: 'AED';
        $lhPhone = trim((string) $settings->phone);
        $lhEmail = trim((string) $settings->email);
        $lhAddress = collect([$settings->address, $settings->emirate])->filter()->implode(', ');
        $quoteRefDate = $order->quotationReferenceDate();
        $supplierLines = collect(preg_split('/\r\n|\r|\n/', (string) $supplier?->address))
            ->map(fn ($line) => trim((string) $line))
            ->filter();
        $buyer = trim((string) ($order->buyer_details ?: \App\Models\PurchaseOrder::defaultBuyerDetails($settings)));
        $buyerName = trim((string) strtok($buyer, "\r\n")) ?: (string) $settings->name;
        $fmtQty = fn ($n) => number_format((float) $n, 2);
        $fmtPct = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, '.', ''), '0'), '.').'%';
    @endphp

    <div class="doc-footer">
        <table>
            <tr>
                <td width="80%">
                    {{ mb_strtoupper((string) $settings->name) }} | LPO No. {{ $order->number }}
                    @if ($settings->trn) &nbsp;|&nbsp; VAT No. {{ $settings->trn }} @endif
                </td>
                <td width="20%" class="right">Page <span class="page-num"></span></td>
            </tr>
        </table>
    </div>

    <table class="header">
        <tr>
            <td width="55%">
                @if ($logoDataUri)
                    <img src="{{ $logoDataUri }}" class="logo">
                @else
                    <div style="font-size: 20px; font-weight: bold; letter-spacing: 0.5px;">ZENAURA SANITARY TRADING</div>
                @endif
            </td>
            <td width="45%" class="co-contact">
                @if ($lhPhone) {{ $lhPhone }}<br> @endif
                @if ($lhEmail) {{ $lhEmail }}<br> @endif
                @if ($lhAddress) {{ $lhAddress }}<br> @endif
                @if ($settings->trn) VAT No. {{ $settings->trn }} @endif
            </td>
        </tr>
    </table>

    <div class="doc-title">LOCAL PURCHASE ORDER</div>

    @if ($order->isCancelled())
        <div class="cancelled">
            CANCELLED{{ $order->cancelled_at ? ' ON '.$order->cancelled_at->format('d/m/Y') : '' }}{{ $order->cancel_reason ? ' — '.mb_strtoupper($order->cancel_reason) : '' }}
        </div>
    @endif

    <table class="meta">
        <tr>
            <td class="k">LPO No.</td>
            <td class="v">{{ $order->number }}</td>
            <td class="k">LPO Date</td>
            <td class="v">{{ $order->lpo_date->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td class="k">Quotation No.</td>
            <td class="v">{{ $order->quotationReference() }}</td>
            <td class="k">Quotation Date</td>
            <td class="v">{{ $quoteRefDate?->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td class="k">Payment Terms</td>
            <td class="v">{{ $order->payment_terms }}</td>
            <td class="k">Delivery Terms</td>
            <td class="v">{{ $order->delivery_terms }}</td>
        </tr>
        <tr>
            <td class="k">Delivery Availability</td>
            <td class="v">{{ $order->delivery_availability }}</td>
            <td class="k">Customer Ref.</td>
            <td class="v">{{ $order->customer_reference }}</td>
        </tr>
    </table>

    <table class="parties">
        <tr>
            <td class="col" width="50%" style="padding-right: 6px;">
                <div class="box">
                    <div class="cap">SUPPLIER</div>
                    <div class="body">
                        <strong>{{ mb_strtoupper((string) $supplier?->name) }}</strong><br>
                        @if ($supplier?->contact_person) Attn: {{ $supplier->contact_person }}<br> @endif
                        @foreach ($supplierLines as $line)
                            {{ $line }}<br>
                        @endforeach
                        @if ($supplier?->phone) Tel: {{ $supplier->phone }}<br> @endif
                        @if ($supplier?->email) Email: {{ $supplier->email }}<br> @endif
                        @if ($supplier?->trn) VAT No. {{ $supplier->trn }} @endif
                    </div>
                </div>
            </td>
            <td class="col" width="50%" style="padding-left: 6px;">
                <div class="box">
                    <div class="cap">BUYER</div>
                    <div class="body">{!! nl2br(e($buyer)) !!}</div>
                </div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th width="5%">S.No</th>
                <th width="14%">Article No.</th>
                <th>Description</th>
                <th width="6%">Unit</th>
                <th width="7%" class="right">Qty</th>
                <th width="10%" class="right">Unit Price</th>
                <th width="6%" class="right">Disc.</th>
                <th width="10%" class="right">Net SP</th>
                <th width="13%" class="right">Value (Ex VAT)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ mb_strtoupper((string) $item->code) }}</td>
                    <td>{{ mb_strtoupper((string) $item->description) }}</td>
                    <td>{{ mb_strtoupper((string) $item->unit) }}</td>
                    <td class="right">{{ $fmtQty($item->qty) }}</td>
                    <td class="right">{{ number_format((float) $item->unit_price, 2) }}</td>
                    <td class="right">{{ (float) $item->discount_percent > 0 ? $fmtPct($item->discount_percent) : '—' }}</td>
                    <td class="right">{{ number_format($item->netUnitPrice(), 2) }}</td>
                    <td class="right">{{ number_format((float) $item->net_amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="sum-wrap">
        <tr>
            <td width="52%"></td>
            <td width="48%">
                <table class="sum">
                    <tr>
                        <td class="lab">Gross Amount (Excl. VAT)</td>
                        <td class="cur">{{ $currency }}</td>
                        <td class="right">{{ number_format((float) $order->subtotal, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="lab">Discount</td>
                        <td class="cur">{{ $currency }}</td>
                        <td class="right">{{ number_format((float) $order->discount_total, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="lab">Discounted Amount</td>
                        <td class="cur">{{ $currency }}</td>
                        <td class="right">{{ number_format($order->netExclVat(), 2) }}</td>
                    </tr>
                    <tr>
                        <td class="lab">{{ $order->vatLabel() }}</td>
                        <td class="cur">{{ $currency }}</td>
                        <td class="right">{{ number_format((float) $order->vat_total, 2) }}</td>
                    </tr>
                    <tr class="net">
                        <td class="lab">TOTAL LPO VALUE</td>
                        <td class="cur">{{ $currency }}</td>
                        <td class="right">{{ number_format((float) $order->grand_total, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="terms">
        <h4>Purchase Order Terms</h4>
        @foreach ($order->termLines() as $line)
            {{ $loop->iteration }}. {{ preg_replace('/^\d+[\.\)]\s*/', '', $line) }}<br>
        @endforeach
    </div>

    <table class="signs">
        <tr>
            <td width="50%">
                <div class="sign-title">For {{ \Illuminate\Support\Str::title(mb_strtolower($buyerName)) }}</div>
                <table class="sign-media">
                    <tr>
                        <td>
                            @if ($signatureDataUri)<img src="{{ $signatureDataUri }}" class="sign-img">@endif
                            @if ($stampDataUri)<img src="{{ $stampDataUri }}" class="stamp-img">@endif
                            @if (! $signatureDataUri && ! $stampDataUri)<div style="height: 96px;"></div>@endif
                        </td>
                    </tr>
                </table>
                <div class="sign-label">Authorized Signature &amp; Stamp</div>
            </td>
            <td width="50%">
                <div class="sign-title">Supplier Acknowledgement</div>
                <div style="height: 100px;"></div>
                <div class="sign-label">Authorized Signature &amp; Stamp</div>
            </td>
        </tr>
    </table>
</body>
</html>
