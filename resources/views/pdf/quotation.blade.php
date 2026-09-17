<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $quotation->number }}</title>
    <style>
        @page { margin: 80px 36px 62px 36px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
        .muted { color: #555; }
        .right { text-align: right; }
        table, th, td { font-size: 10px; }
        table { width: 100%; border-collapse: collapse; }
        .pdf-header { position: fixed; top: -70px; left: 0; right: 0; }
        .pdf-header td { vertical-align: top; }
        .logo { max-height: 56px; max-width: 220px; }
        .co-contact { font-size: 10px; line-height: 1.5; color: #222; text-align: right; }
        .doc-title { font-size: 22px; font-weight: bold; text-align: center; margin: 0 0 12px; color: #111; }
        .doc-sub { text-align: center; font-size: 10px; color: #444; margin-top: 2px; }
        .pdf-footer { position: fixed; bottom: -50px; left: 0; right: 0; height: 40px; border-top: 0.8px solid #bbb; padding-top: 6px; font-size: 10px; color: #444; }
        .page-num:after { content: counter(page); }
        h1 { font-size: 18px; margin: 0 0 4px; letter-spacing: 1px; color: #0e3d44; }
        .meta { margin-bottom: 10px; }
        .meta td { vertical-align: top; padding: 0; font-size: 10px; }
        .box { border: 1px solid #d5d5d5; padding: 8px 10px; font-size: 10px; line-height: 1.4; }
        .box-title { background: #0e3d44; color: #fff; font-size: 10px; letter-spacing: 0.08em; text-transform: uppercase; padding: 4px 8px; }
        .kv td { padding: 3px 0; font-size: 10px; }
        .items th { background: #0e3d44; color: #fff; font-size: 10px; font-weight: bold; text-transform: uppercase; padding: 5px 3px 6px; }
        .items td { padding: 4px 3px; border-bottom: 0.5px solid #ddd; vertical-align: middle; font-size: 10px; }
        .heading td { background: #4a4a4a; color: #fff; font-weight: bold; font-size: 10px; padding: 6px 4px; }
        .subhead td { background: #d9d9d9; font-weight: bold; font-size: 10px; }
        .thumb { width: 32px; height: 32px; object-fit: cover; }
        .totals td { padding: 4px 6px; border: none; font-size: 10px; }
        .grand { font-size: 10px; font-weight: bold; }
        .terms { font-size: 10px; color: #555; margin-top: 12px; line-height: 1.5; }
        img { border: 0; }
    </style>
</head>
<body>
    @php
        $logoDataUri = $logoDataUri ?? null;
        $lhPhone = trim((string) $settings->phone);
        $lhEmail = trim((string) $settings->email);
        $lhAddress = collect([$settings->address, $settings->emirate])->filter()->implode(', ');
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
                <td width="78%">
                    {{ collect([$lhAddress, $lhPhone, $lhEmail])->filter()->implode(' | ') }}
                    @if ($settings->trn) &nbsp;|&nbsp; VAT No. {{ $settings->trn }} @endif
                </td>
                <td width="22%" class="right">Page <span class="page-num"></span></td>
            </tr>
        </table>
    </div>

    <div class="doc-title">Quotation</div>

    <table class="meta">
        <tr>
            <td width="56%" style="padding-right: 8px;">
                <div class="box-title">Customer / delivery</div>
                <div class="box">
                    <strong>Customer Name:</strong> {{ $quotation->client->name }}<br>
                    <strong>Delivery Details:</strong><br>
                    {!! nl2br(e($quotation->delivery_details ?: trim($quotation->client->name."\n".$quotation->client->address."\n".$quotation->client->emirate))) !!}<br>
                    @if ($quotation->client->phone)
                        T: {{ $quotation->client->phone }}<br>
                    @endif
                    <strong>Customer VAT no.</strong> {{ $quotation->client->trn }}
                </div>
            </td>
            <td width="44%">
                <div class="box-title">Quotation details</div>
                <div class="box">
                    <table class="kv">
                        <tr><td class="muted" width="48%">Quotation no.</td><td><strong>{{ $quotation->number }}</strong></td></tr>
                        @if ($quotation->revision)
                            <tr><td class="muted">Revision</td><td>{{ $quotation->revisionLabel() }}</td></tr>
                        @endif
                        <tr><td class="muted">Delivery</td><td>{{ $quotation->delivery }}</td></tr>
                        <tr><td class="muted">Payment terms</td><td>{{ $quotation->payment_terms }}</td></tr>
                        <tr><td class="muted">Customer Ref.</td><td>{{ $quotation->reference }}</td></tr>
                        <tr><td class="muted">Date</td><td>{{ $quotation->quote_date->format('d/m/Y') }}</td></tr>
                        <tr><td class="muted">Validity</td><td>{{ $quotation->valid_until?->format('d/m/Y') }}</td></tr>
                        <tr><td class="muted">Availability</td><td>{{ $quotation->delivery_availability }}</td></tr>
                        <tr><td class="muted">Currency</td><td>AED</td></tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th width="8%"></th>
                <th width="13%">Article</th>
                <th>Description</th>
                <th width="6%">Unit</th>
                <th width="6%" class="right">Qty</th>
                <th width="10%" class="right">Sales Price</th>
                <th width="7%" class="right">Disc %</th>
                <th width="11%" class="right">Value (Ex. VAT)</th>
                <th width="9%" class="right">VAT Value</th>
                <th width="11%" class="right">Value (Inc. VAT)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($quotation->groupedForPdf() as $group)
                @if ($group['heading'] !== '')
                    <tr class="heading">
                        <td colspan="9">{{ strtoupper($group['heading']) }}</td>
                        <td class="right">{{ number_format($group['total'], 2) }}</td>
                    </tr>
                @endif
                @foreach ($group['subs'] as $sub)
                    @if ($sub['title'] !== '')
                        <tr class="subhead">
                            <td colspan="10">{{ strtoupper($sub['title']) }}</td>
                        </tr>
                    @endif
                    @foreach ($sub['items'] as $item)
                        @php
                            $net = round(((float) $item->amount) * (1 - ((float) $item->discount_percent / 100)), 2);
                            $discountPercent = (float) $item->discount_percent;
                            $itemImage = $itemImages[$item->id] ?? null;
                        @endphp
                        <tr>
                            <td>
                                @if ($itemImage)
                                    <img src="{{ $itemImage }}" class="thumb">
                                @endif
                            </td>
                            <td>{{ mb_strtoupper((string) $item->code) }}</td>
                            <td>{{ mb_strtoupper((string) $item->description) }}</td>
                            <td>{{ strtoupper($item->unit) }}</td>
                            <td class="right">{{ rtrim(rtrim(number_format((float) $item->qty, 3, '.', ''), '0'), '.') }}</td>
                            <td class="right">{{ number_format((float) $item->selling_price, 2) }}</td>
                            <td class="right">{{ $discountPercent > 0 ? number_format($discountPercent, 2) : '—' }}</td>
                            <td class="right">{{ number_format($net, 2) }}</td>
                            <td class="right">{{ number_format((float) $item->vat_amount, 2) }}</td>
                            <td class="right">{{ number_format((float) $item->line_total, 2) }}</td>
                        </tr>
                    @endforeach
                @endforeach
            @endforeach
        </tbody>
    </table>

    <br>
    <table class="totals">
        <tr>
            <td width="58%"></td>
            <td width="24%" class="muted">Gross amount</td>
            <td width="18%" class="right">AED {{ number_format((float) $quotation->subtotal, 2) }}</td>
        </tr>
        <tr>
            <td></td>
            <td class="muted">Discount</td>
            <td class="right">AED {{ number_format((float) $quotation->discount_total, 2) }}</td>
        </tr>
        <tr>
            <td></td>
            <td class="muted">Total amount (Excl. VAT)</td>
            <td class="right">AED {{ number_format((float) $quotation->subtotal - (float) $quotation->discount_total, 2) }}</td>
        </tr>
        <tr>
            <td></td>
            <td class="muted">VAT Amount</td>
            <td class="right">AED {{ number_format((float) $quotation->vat_total, 2) }}</td>
        </tr>
        <tr>
            <td></td>
            <td class="grand">Net Amount</td>
            <td class="right grand">AED {{ number_format((float) $quotation->grand_total, 2) }}</td>
        </tr>
    </table>

    @if ($quotation->terms)
        <div class="terms"><strong>Terms</strong><br>{!! nl2br(e($quotation->terms)) !!}</div>
    @endif
</body>
</html>
