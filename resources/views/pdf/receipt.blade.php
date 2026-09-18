<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $receipt->number }}</title>
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
        .meta { margin-bottom: 10px; }
        .meta td { border: 0.8px solid #000; padding: 4px 6px; vertical-align: top; font-size: 10px; }
        .meta .k { background: #e6e6e6; width: 18%; }
        .meta .v { width: 32%; }
        .amount { margin: 12px 0; border: 1.2px solid #000; }
        .amount td { padding: 8px 10px; }
        .amount .lab { background: #e6e6e6; width: 28%; font-weight: bold; }
        .amount .val { font-size: 16px; font-weight: bold; }
        .words { border: 0.8px solid #000; padding: 8px 10px; margin-bottom: 12px; line-height: 1.45; }
        .being { border: 0.8px solid #000; padding: 8px 10px; margin-bottom: 12px; line-height: 1.45; }
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
        $amount = (float) $receipt->amount;
        $invoicedLines = collect([
            $client?->name,
            $client?->address,
            $client?->emirate,
            $client?->emirate ? 'United Arab Emirates' : null,
            $client?->phone ? 'T.'.$client->phone : null,
        ])->filter()->values();
        $trn = trim((string) ($client?->trn ?: '')) ?: 'NA';
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

    <div class="doc-title">Receipt Voucher</div>

    <table class="boxes">
        <tr>
            <td width="32%" style="padding-right: 6px;">
                <table class="kv">
                    <tr>
                        <td class="k">Receipt date</td>
                        <td class="v">{{ $receipt->received_date->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <td class="k">Currency</td>
                        <td class="v">{{ $currency }}</td>
                    </tr>
                    <tr>
                        <td class="k">Payment mode</td>
                        <td class="v">{{ $receipt->payment_mode }}</td>
                    </tr>
                </table>
            </td>
            <td width="34%" style="padding-right: 6px;">
                <div class="box">
                    <div class="cap">Received from</div>
                    <div class="body">
                        @foreach ($invoicedLines as $line)
                            {{ $line }}<br>
                        @endforeach
                    </div>
                </div>
            </td>
            <td width="34%">
                <div class="box">
                    <div class="cap">Against</div>
                    <div class="body">
                        Proforma {{ $proforma->number }}<br>
                        {{ $proforma->percentLabel() }} of {{ $quotation->number }}<br>
                        Customer VAT no. {{ $trn }}
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <table class="meta">
        <tr>
            <td class="k">Receipt no.</td>
            <td class="v">{{ $receipt->number }}</td>
            <td class="k">Reference</td>
            <td class="v">{{ $receipt->reference ?: '—' }}</td>
        </tr>
    </table>

    <table class="amount">
        <tr>
            <td class="lab">Amount received</td>
            <td class="val">{{ $currency }} {{ number_format($amount, 2) }}</td>
        </tr>
    </table>

    <div class="words">
        <strong>Amount in words:</strong> {{ aed_in_words($amount) }}
    </div>

    <div class="being">
        <strong>Being:</strong>
        Payment received against Proforma Invoice {{ $proforma->number }}
        ({{ $proforma->percentLabel() }}) for quotation {{ $quotation->number }}.
        @if (trim((string) $proforma->description))
            {{ $proforma->description }}
        @endif
    </div>

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
