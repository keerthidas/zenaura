<?php

use App\Models\CompanySetting;

if (! function_exists('aed')) {
    function aed(mixed $amount): string
    {
        $currency = CompanySetting::current()?->currency ?: 'AED';

        return $currency.' '.number_format((float) $amount, 2);
    }
}

if (! function_exists('aed_in_words')) {
    function aed_in_words(mixed $amount): string
    {
        return \App\Support\MoneyWords::aed((float) $amount);
    }
}

if (! function_exists('public_media_url')) {
    function public_media_url(?string $path): string
    {
        if (! $path) {
            return '';
        }

        $path = ltrim(str_replace('\\', '/', $path), '/');

        return url('media/'.$path);
    }
}

if (! function_exists('pdf_data_uri')) {
    function pdf_data_uri(?string $path, ?int $maxEdge = null, int $jpegQuality = 75): ?string
    {
        if (! $path || ! \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
            return null;
        }

        $full = \Illuminate\Support\Facades\Storage::disk('public')->path($path);

        return \App\Support\PdfImage::dataUriFromFile($full, $maxEdge, $jpegQuality);
    }
}

if (! function_exists('pdf_public_data_uri')) {
    function pdf_public_data_uri(?string $relativePath, ?int $maxEdge = null, int $jpegQuality = 75): ?string
    {
        if (! $relativePath) {
            return null;
        }

        $full = public_path(ltrim(str_replace('\\', '/', $relativePath), '/'));

        return \App\Support\PdfImage::dataUriFromFile($full, $maxEdge, $jpegQuality);
    }
}

if (! function_exists('pdf_brand_data_uri')) {
    function pdf_brand_data_uri(?string $uploadedPath, string $defaultPublicPath): ?string
    {
        return pdf_data_uri($uploadedPath) ?: pdf_public_data_uri($defaultPublicPath);
    }
}

if (! function_exists('invoice_qty_unit')) {
    function invoice_qty_unit(?string $unit): string
    {
        $unit = strtoupper(trim((string) $unit));

        return in_array($unit, ['PCS', 'PC', 'EACH', 'EA', ''], true) ? 'Nos' : $unit;
    }
}

if (! function_exists('zenora_emirates')) {
    /**
     * @return list<string>
     */
    function zenora_emirates(): array
    {
        return [
            'Abu Dhabi',
            'Dubai',
            'Sharjah',
            'Ajman',
            'Umm Al Quwain',
            'Ras Al Khaimah',
            'Fujairah',
        ];
    }
}

if (! function_exists('flash_app_toast')) {
    function flash_app_toast(object $component, string $message, string $type = 'success'): void
    {
        $type = $type === 'error' ? 'error' : 'success';
        session()->flash('toast', compact('message', 'type'));
        $component->dispatch('app-toast', message: $message, type: $type);
    }
}
