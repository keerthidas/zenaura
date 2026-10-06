<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $deliveryNote->number }}</title>
    <style>
        @page { margin: 32px 34px 66px 34px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
        table { width: 100%; border-collapse: collapse; }
        td, th { font-size: 10px; }
        .right { text-align: right; }
        .header td { vertical-align: top; }
        .logo { max-height: 56px; max-width: 220px; }
        .company-contact { text-align: right; line-height: 1.5; color: #333; }
        .title { margin: 12px 0; text-align: center; font-size: 21px; font-weight: bold; letter-spacing: .5px; }
        .meta { margin-bottom: 10px; }
        .meta td { border: .8px solid #111; padding: 5px 6px; vertical-align: top; }
        .meta .key { width: 18%; background: #e6e6e6; }
        .meta .value { width: 32%; font-weight: bold; }
        .parties { margin-bottom: 12px; }
        .party-cell { width: 50%; vertical-align: top; }
        .party-box { border: .8px solid #111; }
        .party-title { background: #0e3d44; color: #fff; padding: 5px 7px; font-weight: bold; }
        .party-body { min-height: 56px; padding: 7px; line-height: 1.45; }
        .items { margin-top: 4px; }
        .items th { padding: 6px 4px; background: #0e3d44; color: #fff; text-align: left; }
        .items td { padding: 6px 4px; border-bottom: .5px solid #ccc; vertical-align: top; }
        .items thead { display: table-header-group; }
        .items tbody tr { page-break-inside: avoid; }
        .excess { color: #a16207; font-weight: bold; }
        .remarks { margin-top: 12px; padding: 8px; border: .8px solid #ccc; line-height: 1.45; }
        .confirmation-title { margin-top: 20px; font-size: 12px; font-weight: bold; text-align: center; }
        .signatures { margin-top: 44px; }
        .signatures td { width: 50%; padding: 0 18px; vertical-align: bottom; }
        .company-marks { height: 70px; }
        .signature-image { max-height: 44px; max-width: 90px; vertical-align: bottom; }
        .stamp-image { max-height: 65px; max-width: 75px; margin-left: 8px; vertical-align: bottom; }
        .sign-line { border-top: .8px solid #111; padding-top: 5px; }
        .footer { position: fixed; left: 0; right: 0; bottom: -48px; padding-top: 6px; border-top: .8px solid #bbb; color: #444; }
        .page-number:after { content: counter(page); }
    </style>
</head>
<body>
    @php
        $signatureDataUri = $signatureDataUri ?? null;
        $stampDataUri = $stampDataUri ?? null;
        $client = $deliveryNote->quotation?->client;
        $supplier = $deliveryNote->supplier;
        $companyAddress = collect([$settings->address, $settings->emirate])->filter()->implode(', ');
        $quantity = fn ($value) => rtrim(rtrim(number_format((float) $value, 3, '.', ''), '0'), '.');
    @endphp

    <table class="header">
        <tr>
            <td width="55%">
                @if ($logoDataUri)
                    <img src="{{ $logoDataUri }}" class="logo">
                @else
                    <div style="font-size: 20px; font-weight: bold;">{{ mb_strtoupper((string) $settings->name) }}</div>
                @endif
            </td>
            <td width="45%" class="company-contact">
                @if ($settings->phone) {{ $settings->phone }}<br> @endif
                @if ($settings->email) {{ $settings->email }}<br> @endif
                @if ($companyAddress) {{ $companyAddress }}<br> @endif
                @if ($settings->trn) VAT No. {{ $settings->trn }} @endif
            </td>
        </tr>
    </table>

    <div class="title">DELIVERY NOTE</div>

    <table class="meta">
        <tr>
            <td class="key">Delivery note no.</td>
            <td class="value">{{ $deliveryNote->number }}</td>
            <td class="key">Delivery date</td>
            <td class="value">{{ $deliveryNote->delivery_date->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td class="key">Sales Order No.</td>
            <td class="value">{{ $deliveryNote->quotation?->number }}</td>
            <td class="key">Quotation date</td>
            <td class="value">{{ $deliveryNote->quotation?->quote_date?->format('d/m/Y') }}</td>
        </tr>
    </table>

    <table class="parties">
        <tr>
            <td class="party-cell" style="padding-right: 6px;">
                <div class="party-box">
                    <div class="party-title">CUSTOMER</div>
                    <div class="party-body">
                        <strong>{{ $client?->name }}</strong><br>
                        @if ($client?->address) {{ $client->address }}<br> @endif
                        @if ($client?->emirate) {{ $client->emirate }}, UAE<br> @endif
                        @if ($client?->phone) Tel: {{ $client->phone }} @endif
                    </div>
                </div>
            </td>
            <td class="party-cell" style="padding-left: 6px;">
                <div class="party-box">
                    <div class="party-title">SUPPLIER</div>
                    <div class="party-body">
                        <strong>{{ $supplier?->name }}</strong><br>
                        @if ($supplier?->contact_person) Attn: {{ $supplier->contact_person }}<br> @endif
                        @if ($supplier?->address) {!! nl2br(e($supplier->address)) !!}<br> @endif
                        @if ($supplier?->phone) Tel: {{ $supplier->phone }} @endif
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th width="16%">Article No.</th>
                <th>Description</th>
                <th width="12%" class="right">Qty</th>
                <th width="12%">Unit</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($deliveryNote->items as $item)
                @php
                    $balance = (float) $item->quoted_qty - (float) $item->delivered_before - (float) $item->delivered_qty;
                @endphp
                <tr>
                    <td>{{ mb_strtoupper((string) $item->code) }}</td>
                    <td>{{ $item->description }}</td>
                    <td class="right">{{ $quantity($item->delivered_qty) }}</td>
                    <td>{{ mb_strtoupper((string) $item->unit) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if ($deliveryNote->notes)
        <div class="remarks"><strong>Remarks:</strong> {{ $deliveryNote->notes }}</div>
    @endif

    <div class="confirmation-title">Delivery Confirmation</div>
    <table class="signatures">
        <tr>
            <td>
                @if ($signatureDataUri || $stampDataUri)
                    <div class="company-marks">
                        @if ($signatureDataUri) <img src="{{ $signatureDataUri }}" class="signature-image"> @endif
                        @if ($stampDataUri) <img src="{{ $stampDataUri }}" class="stamp-image"> @endif
                    </div>
                @endif
                <div class="sign-line">For Zenaura Sanitary Trading</div>
            </td>
            <td><div class="sign-line">Received by Customer</div></td>
        </tr>
    </table>

    <div class="footer">
        <table>
            <tr>
                <td width="80%">{{ mb_strtoupper((string) $settings->name) }} | Delivery note {{ $deliveryNote->number }}</td>
                <td width="20%" class="right">Page <span class="page-number"></span></td>
            </tr>
        </table>
    </div>
</body>
</html>