<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $proforma->number }}</title>
    <style>
        @page { margin: 32px 34px 88px 34px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
        table, th, td { font-size: 10px; }
        table { width: 100%; border-collapse: collapse; }
        .right { text-align: right; }
        .center { text-align: center; }
        .header td { vertical-align: top; }
        .logo { max-height: 56px; max-width: 220px; }
        .co-contact { font-size: 10px; line-height: 1.5; color: #222; text-align: right; }
        .doc-title { font-size: 22px; font-weight: bold; text-align: center; margin: 10px 0 12px; color: #111; }
        .boxes { margin-bottom: 8px; }
        .boxes td { vertical-align: top; }
        .box { border: 0.8px solid #000; }
        .box .cap { background: #e6e6e6; padding: 4px 6px; font-size: 10px; border-bottom: 0.8px solid #000; }
        .box .body { padding: 5px 6px; font-size: 10px; line-height: 1.4; min-height: 62px; }
        .kv td { border: 0.8px solid #000; padding: 3px 5px; font-size: 10px; }
        .kv .k { background: #e6e6e6; width: 42%; }
        .kv .v { font-weight: bold; }
        .meta { margin-bottom: 8px; }
        .meta td { border: 0.8px solid #000; padding: 4px 6px; vertical-align: top; font-size: 10px; }
        .meta .k { background: #e6e6e6; width: 16%; }
        .meta .v { width: 17%; }
        .items { margin-top: 2px; }
        .items th { font-size: 10px; font-weight: bold; color: #111; padding: 5px 4px 6px; border-bottom: 0.8px solid #000; vertical-align: bottom; }
        .items td { padding: 8px 4px; border-bottom: 0.4px solid #cfcfcf; vertical-align: top; font-size: 10px; }
        .totals-bar td { background: #e6e6e6; border: 0.8px solid #000; padding: 5px 4px; font-size: 10px; }
        .totals-bar .amt { background: #fff; font-weight: bold; }
        .sum-wrap { margin-top: 14px; page-break-inside: avoid; }
        .sum { border: 1.2px solid #000; }
        .sum td { border: 0.6px solid #000; padding: 4px 6px; font-size: 10px; }
        .sum .lab { background: #e6e6e6; width: 52%; }
        .sum .cur { width: 14%; }
        .sum .net td { font-weight: bold; }
        .split { margin-top: 14px; page-break-inside: avoid; }
        .split td { border: 0.8px solid #000; padding: 0; vertical-align: top; }
        .split .cap { background: #e6e6e6; padding: 5px 8px; font-weight: bold; border-bottom: 0.8px solid #000; }
        .split .amt { padding: 8px 8px 4px; font-size: 13px; font-weight: bold; }
        .split .hint { padding: 0 8px 8px; font-size: 9px; color: #444; }
        .signs { margin-top: 36px; page-break-inside: avoid; }
        .signs td { vertical-align: bottom; padding: 0; }
        .sign-box { width: 220px; margin: 0 auto; }
        .sign-media { width: 100%; margin-bottom: 4px; }
        .sign-media td { text-align: center; vertical-align: bottom; }
        .sign-img { height: 64px; max-width: 180px; }
        .stamp-img { height: 140px; }
        .sign-label { border-top: 0.9px solid #111; padding: 5px 0 0 0; font-size: 10px; }
        .doc-footer { position: fixed; left: 0; right: 0; bottom: -78px; height: 72px; }
        .note { font-size: 10px; line-height: 1.5; color: #222; }
        .page-meta { margin-top: 8px; font-size: 10px; color: #333; }
        .page-num:after { content: counter(page); }
        img { border: 0; }
    </style>
</head>
<body>
    @php
        $logoDataUri = $logoDataUri ?? null;
        $signatureDataUri = $signatureDataUri ?? null;
        $stampDataUri = $stampDataUri ?? null;
        $client = $quotation->client;
        $currency = $settings->currency ?: 'AED';
        $lhPhone = trim((string) $settings->phone);
        $lhEmail = trim((string) $settings->email);
        $lhAddress = collect([$settings->address, $settings->emirate])->filter()->implode(', ');
        $remaining = round(max(0, 100 - (float) $quotation->proformas->where('id', '<=', $proforma->id)->sum('percent')), 2);
        $quoteGrand = (float) $quotation->grand_total;
        $balanceAmount = round($quoteGrand * ($remaining / 100), 2);
        $percentLabel = $proforma->percentLabel();
        $remainingLabel = rtrim(rtrim(number_format($remaining, 2, '.', ''), '0'), '.').'%';
        $paymentTerms = trim((string) ($proforma->payment_terms ?: $quotation->payment_terms)) ?: 'CASH IN ADVANCE';
        $deliveryTerms = trim((string) ($proforma->delivery_terms ?: $quotation->delivery_availability ?: $quotation->delivery)) ?: 'TO BE CONFIRMED';
        $trn = trim((string) ($client?->trn ?: '')) ?: 'NA';
        $qtyNote = 'As per attached details and quantities in QTN '.$quotation->number;
        $invoicedLines = collect([
            $client?->address,
            $client?->emirate,
            $client?->emirate ? 'United Arab Emirates' : null,
            $client?->name,
            $client?->phone ? 'T.'.$client->phone : null,
        ])->filter()->values();
        $net = (float) $proforma->subtotal;
        $vatTotal = (float) $proforma->vat_total;
        $grand = (float) $proforma->grand_total;
    @endphp

    <div class="doc-footer">
        <div class="note">
            Please send all remittances for payments and queries to:
            <strong>{{ $lhEmail }}</strong>
        </div>
        <table class="page-meta">
            <tr>
                <td width="50%">Page <span class="page-num"></span></td>
                <td width="50%" class="right">@if ($settings->trn) VAT No. {{ $settings->trn }} @endif</td>
            </tr>
        </table>
    </div>

    <table class="header">
        <tr>
            <td width="55%">
                @if ($logoDataUri)
                    <img src="{{ $logoDataUri }}" class="logo">
                @else
                    <div style="font-size: 22px; font-weight: bold; letter-spacing: 0.5px;">ZENAURA SANITARY</div>
                @endif
            </td>
            <td width="45%" class="co-contact">
                @if ($lhPhone) {{ $lhPhone }}<br> @endif
                @if ($lhEmail) {{ $lhEmail }}<br> @endif
                @if ($lhAddress) {{ $lhAddress }} @endif
            </td>
        </tr>
    </table>

    <div class="doc-title">Proforma Invoice</div>

    <table class="boxes">
        <tr>
            <td width="28%" style="padding-right: 6px;">
                <table class="kv">
                    <tr>
                        <td class="k">Invoice date</td>
                        <td class="v">{{ $proforma->invoice_date->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <td class="k">Currency</td>
                        <td class="v">{{ $currency }}</td>
                    </tr>
                </table>
            </td>
            <td width="36%" style="padding-right: 6px;">
                <div class="box">
                    <div class="cap">Customer</div>
                    <div class="body">
                        @foreach ($invoicedLines as $line)
                            {{ $line }}<br>
                        @endforeach
                    </div>
                </div>
            </td>
            <td width="36%">
                <div class="box">
                    <div class="cap">Quotation</div>
                    <div class="body">
                        <strong>{{ $quotation->number }}</strong><br>
                        Customer VAT no. {{ $trn }}
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <table class="meta">
        <tr>
            <td class="k">Proforma no.</td>
            <td class="v">{{ $proforma->number }}</td>
            <td class="k">Payment terms</td>
            <td class="v">{{ $paymentTerms }}</td>
            <td class="k">Delivery terms</td>
            <td class="v">{{ $deliveryTerms }}</td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th width="8%">S.No</th>
                <th width="48%">Description</th>
                <th width="28%">Qty</th>
                <th width="16%" class="right">Value<br>(Ex VAT)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1</td>
                <td>{{ $proforma->description }}</td>
                <td>{{ $qtyNote }}</td>
                <td class="right">{{ number_format($net, 2) }}</td>
            </tr>
            <tr class="totals-bar">
                <td colspan="3">Total amount (Excl. VAT)</td>
                <td class="amt right">{{ $currency }} {{ number_format($net, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="sum-wrap">
        <tr>
            <td width="58%"></td>
            <td width="42%">
                <table class="sum">
                    <tr>
                        <td class="lab">Total amount (Excl. VAT)</td>
                        <td class="cur">{{ $currency }}</td>
                        <td class="right">{{ number_format($net, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="lab">VAT amount</td>
                        <td class="cur">{{ $currency }}</td>
                        <td class="right">{{ number_format($vatTotal, 2) }}</td>
                    </tr>
                    <tr class="net">
                        <td class="lab">FINAL AMOUNT</td>
                        <td class="cur">{{ $currency }}</td>
                        <td class="right">{{ number_format($grand, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="split">
        <tr>
            <td width="34%">
                <div class="cap">Quotation total</div>
                <div class="amt">{{ $currency }} {{ number_format($quoteGrand, 2) }}</div>
            </td>
            <td width="33%">
                <div class="cap">{{ $percentLabel }} Advance to be Paid</div>
                <div class="amt">{{ $currency }} {{ number_format($grand, 2) }}</div>
            </td>
            <td width="33%">
                <div class="cap">{{ $remaining > 0 ? $remainingLabel.' Balance to be Paid' : 'Balance Paid' }}</div>
                <div class="amt">{{ $currency }} {{ number_format($balanceAmount, 2) }}</div>
            </td>
        </tr>
    </table>

    <table class="signs">
        <tr>
            <td width="50%">
                <div class="sign-box">
                    @if ($signatureDataUri)
                        <table class="sign-media"><tr><td class="center"><img src="{{ $signatureDataUri }}" class="sign-img"></td></tr></table>
                    @else
                        <div style="height: 64px;"></div>
                    @endif
                    <div class="sign-label">Signature</div>
                </div>
            </td>
            <td width="50%">
                <div class="sign-box">
                    @if ($stampDataUri)
                        <table class="sign-media"><tr><td class="center"><img src="{{ $stampDataUri }}" class="stamp-img"></td></tr></table>
                    @else
                        <div style="height: 140px;"></div>
                    @endif
                    <div class="sign-label">Co's Stamp</div>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
