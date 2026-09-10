@php
    $discountDisplay = (float) $quotation->discount_total == 0.0
        ? '00.0'
        : number_format((float) $quotation->discount_total, 2);
    $vatLabel = rtrim(rtrim(number_format((float) ($vatPercent ?? $settings->default_vat_percent ?? 5), 2), '0'), '.').'% VAT (AED)';
@endphp
<tr>
    <td colspan="4" class="blank"></td>
    <td>Total (AED)</td>
    <td class="right">{{ number_format((float) $quotation->subtotal, 2) }}</td>
</tr>
<tr>
    <td colspan="4" class="blank"></td>
    <td>Discount (AED)</td>
    <td class="right">{{ $discountDisplay }}</td>
</tr>
<tr>
    <td colspan="4" class="blank"></td>
    <td>Sub Total (AED)</td>
    <td class="right">{{ number_format($net, 2) }}</td>
</tr>
<tr>
    <td colspan="4" class="blank"></td>
    <td>{{ $vatLabel }}</td>
    <td class="right">{{ number_format((float) $quotation->vat_total, 2) }}</td>
</tr>
<tr class="grand">
    <td colspan="4" class="blank"></td>
    <td>Grand Total (AED)</td>
    <td class="right">{{ number_format((float) $quotation->grand_total, 2) }}</td>
</tr>
