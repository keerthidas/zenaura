<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $quotation->invoice_number }}</title>
    <style>
        @page { margin: 32px 34px 88px 34px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
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
        .box .cap { background: #e6e6e6; padding: 4px 6px; font-size: 9.5px; border-bottom: 0.8px solid #000; }
        .box .body { padding: 5px 6px; font-size: 9.5px; line-height: 1.4; min-height: 62px; }
        .kv td { border: 0.8px solid #000; padding: 3px 5px; font-size: 9.5px; }
        .kv .k { background: #e6e6e6; width: 38%; }
        .kv .v { font-weight: bold; }
        .kv .currency { font-size: 14px; }
        .meta { margin-bottom: 8px; }
        .meta td { border: 0.8px solid #000; padding: 4px 6px; vertical-align: top; font-size: 9.5px; }
        .meta .k { background: #e6e6e6; width: 16%; }
        .meta .v { width: 17%; }
        .items { margin-top: 2px; }
        .items th { font-size: 8.5px; font-weight: bold; color: #111; padding: 5px 3px 6px; border-bottom: 0.8px solid #000; vertical-align: bottom; }
        .items td { padding: 4px 3px; border-bottom: 0.4px solid #cfcfcf; vertical-align: top; font-size: 9px; }
        .items .code { color: #0e3d44; font-weight: bold; word-wrap: break-word; }
        .group td { color: #1d4ed8; font-weight: bold; font-size: 9.5px; border-bottom: none; padding-top: 7px; }
        .subhead td { font-weight: bold; font-size: 9px; }
        .totals-bar td { background: #e6e6e6; border: 0.8px solid #000; padding: 5px 4px; font-size: 9.5px; }
        .totals-bar .amt { background: #fff; font-weight: bold; }
        .sum-wrap { margin-top: 14px; page-break-inside: avoid; }
        .sum { border: 1.2px solid #000; }
        .sum td { border: 0.6px solid #000; padding: 4px 6px; font-size: 10px; }
        .sum .lab { background: #e6e6e6; width: 52%; }
        .sum .cur { width: 14%; }
        .sum .net td { font-weight: bold; }
        .sum .net .lab { background: #e6e6e6; }
        .signs { margin-top: 36px; page-break-inside: avoid; }
        .signs td { vertical-align: bottom; padding: 0; }
        .sign-box { width: 210px; }
        .sign-img { height: 52px; max-width: 170px; display: block; margin: 0 0 8px 0; }
        .stamp-img { height: 98px; max-width: 110px; display: block; margin: 0 0 4px 0; }
        .sign-label { border-top: 0.9px solid #111; padding: 5px 0 0 0; font-size: 11px; }
        .doc-footer { position: fixed; left: 0; right: 0; bottom: -78px; height: 72px; }
        .note { font-size: 10px; line-height: 1.5; color: #222; }
        .page-meta { margin-top: 8px; font-size: 9px; color: #333; }
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
        $invoiceDate = optional($quotation->invoice_date)->format('d/m/Y');
        $orderedOn = optional($quotation->quote_date)->format('d/m/Y');
        $dateRequired = optional($quotation->valid_until)->format('d/m/Y');
        $gross = (float) $quotation->subtotal;
        $discount = (float) $quotation->discount_total;
        $vatTotal = (float) $quotation->vat_total;
        $grand = (float) $quotation->grand_total;
        $discountCell = $discount > 0 ? '-'.number_format($discount, 2) : number_format(0, 2);
        $lhPhone = '+971 54 783 4673';
        $lhEmail = 'zenauratrading@outlook.com';
        $lhAddress = '26th Floor, Amber Gem Tower, Ajman';
        $invoicedLines = collect([
            $client->address,
            $client->emirate,
            $client->emirate ? 'United Arab Emirates' : null,
            $client->name,
            $client->phone ? 'T.'.$client->phone : null,
        ])->filter()->values();
        $shippedText = trim((string) $quotation->delivery_details);
    @endphp

    <div class="doc-footer">
        <div class="note">
            This is a system generated invoice. Hence, no stamp or signature is required.<br>
            Please send all remittances for payments and queries to:<br>
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
                {{ $lhPhone }}<br>
                {{ $lhEmail }}<br>
                {{ $lhAddress }}
            </td>
        </tr>
    </table>

    <div class="doc-title">Tax Invoice</div>

    <table class="boxes">
        <tr>
            <td width="24%" style="padding-right: 6px;">
                <table class="kv">
                    <tr>
                        <td class="k">Ordered on</td>
                        <td class="v">{{ $orderedOn }}</td>
                    </tr>
                    <tr>
                        <td class="k">Date required</td>
                        <td class="v">{{ $dateRequired }}</td>
                    </tr>
                    <tr>
                        <td class="k">Currency</td>
                        <td class="v currency">{{ $currency }}</td>
                    </tr>
                </table>
            </td>
            <td width="38%" style="padding-right: 6px;">
                <div class="box">
                    <div class="cap">Invoiced To:</div>
                    <div class="body">
                        @foreach ($invoicedLines as $line)
                            {{ $line }}<br>
                        @endforeach
                    </div>
                </div>
            </td>
            <td width="38%">
                <div class="box">
                    <div class="cap">Shipped To:</div>
                    <div class="body">
                        @if ($shippedText)
                            {!! nl2br(e($shippedText)) !!}
                        @else
                            @foreach ($invoicedLines as $line)
                                {{ $line }}<br>
                            @endforeach
                        @endif
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <table class="meta">
        <tr>
            <td class="k">Sales order no.</td>
            <td class="v">{{ $quotation->number }}</td>
            <td class="k">Payment terms</td>
            <td class="v">{{ $quotation->payment_terms }}</td>
            <td class="k">Customer ref.</td>
            <td class="v">{{ $quotation->reference }}</td>
        </tr>
        <tr>
            <td class="k">Customer VAT no.</td>
            <td class="v">{{ $client->trn }}</td>
            <td class="k">Invoice no.</td>
            <td class="v">{{ $quotation->invoice_number }}</td>
            <td class="k">Invoiced on</td>
            <td class="v">{{ $invoiceDate }}</td>
        </tr>
        <tr>
            <td class="k">Payment due on</td>
            <td class="v">{{ $invoiceDate }}</td>
            <td colspan="4" style="border: 0; background: transparent;"></td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th width="14%">Article number</th>
                <th width="26%">Description</th>
                <th width="6%" class="right">Qty</th>
                <th width="6%">Unit</th>
                <th width="8%" class="right">Price</th>
                <th width="8%" class="right">Discount%</th>
                <th width="9%" class="right">Value<br>(Ex VAT)</th>
                <th width="6%" class="right">VAT%</th>
                <th width="8%" class="right">VAT Value</th>
                <th width="9%" class="right">Value<br>(Inc VAT)<br>{{ number_format($grand, 2) }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($quotation->groupedForPdf() as $group)
                @if ($group['heading'] !== '')
                    <tr class="group">
                        <td colspan="9">{{ $group['heading'] }}</td>
                        <td class="right">{{ number_format($group['total'], 2) }}</td>
                    </tr>
                @endif
                @foreach ($group['subs'] as $sub)
                    @if ($sub['title'] !== '')
                        <tr class="subhead">
                            <td colspan="10">{{ $sub['title'] }}</td>
                        </tr>
                    @endif
                    @foreach ($sub['items'] as $item)
                        @php
                            $disc = (float) $item->discount_percent;
                            $unit = strtoupper(trim((string) $item->unit));
                            $unit = in_array($unit, ['PCS', 'PC', 'NOS', 'EA', 'EACH', ''], true) ? 'EACH' : $unit;
                            $vatPct = rtrim(rtrim(number_format((float) $item->vat_percent, 2), '0'), '.');
                            $discPct = rtrim(rtrim(number_format($disc, 2), '0'), '.');
                        @endphp
                        <tr>
                            <td class="code">{{ $item->code }}</td>
                            <td>{{ $item->description }}</td>
                            <td class="right">{{ number_format((float) $item->qty, 2) }}</td>
                            <td>{{ $unit }}</td>
                            <td class="right">{{ number_format((float) $item->selling_price, 2) }}</td>
                            <td class="right">{{ $disc > 0 ? $discPct.' %' : '' }}</td>
                            <td class="right">{{ number_format($item->netAmount(), 2) }}</td>
                            <td class="right">{{ $vatPct }}%</td>
                            <td class="right">{{ number_format((float) $item->vat_amount, 2) }}</td>
                            <td class="right">{{ number_format((float) $item->line_total, 2) }}</td>
                        </tr>
                    @endforeach
                @endforeach
            @endforeach
            <tr class="totals-bar">
                <td colspan="2">Total amount (Excl. VAT)</td>
                <td colspan="2" class="amt">{{ number_format($gross, 2) }}</td>
                <td>Discount</td>
                <td class="amt">{{ $discountCell }}</td>
                <td>VAT amount</td>
                <td class="amt">{{ number_format($vatTotal, 2) }}</td>
                <td><strong>Net Amount</strong></td>
                <td class="amt right"><strong>{{ $currency }} {{ number_format($grand, 2) }}</strong></td>
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
                        <td class="right">{{ number_format($gross, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="lab">Discount</td>
                        <td class="cur">{{ $currency }}</td>
                        <td class="right">{{ $discountCell }}</td>
                    </tr>
                    <tr>
                        <td class="lab">VAT amount</td>
                        <td class="cur">{{ $currency }}</td>
                        <td class="right">{{ number_format($vatTotal, 2) }}</td>
                    </tr>
                    <tr class="net">
                        <td class="lab">Net Amount</td>
                        <td class="cur">{{ $currency }}</td>
                        <td class="right">{{ number_format($grand, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="signs">
        <tr>
            <td width="50%">
                <div class="sign-box">
                    @if ($signatureDataUri)
                        <img src="{{ $signatureDataUri }}" class="sign-img">
                    @else
                        <div style="height: 52px;"></div>
                    @endif
                    <div class="sign-label">Signature</div>
                </div>
            </td>
            <td width="50%">
                <div class="sign-box">
                    @if ($stampDataUri)
                        <img src="{{ $stampDataUri }}" class="stamp-img">
                    @else
                        <div style="height: 98px;"></div>
                    @endif
                    <div class="sign-label">Co's Stamp</div>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
