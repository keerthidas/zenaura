<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Models\Quotation;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class QuotationPdfController extends Controller
{
    public function __invoke(Quotation $quotation): Response
    {
        $quotation->load(['client', 'items.product.images']);
        $settings = CompanySetting::current();
        $logoDataUri = pdf_brand_data_uri($settings->logo_path, 'images/logo.png');
        $itemImages = [];

        foreach ($quotation->items as $item) {
            $path = $item->image_path ?: $item->product?->images->first()?->path;
            if ($path) {
                $itemImages[$item->id] = pdf_data_uri($path);
            }
        }

        $pdf = Pdf::loadView('pdf.quotation', [
            'quotation' => $quotation,
            'settings' => $settings,
            'logoDataUri' => $logoDataUri,
            'itemImages' => $itemImages,
        ])->setPaper('a4', 'portrait');

        $filename = str_replace(['/', '\\'], '-', $quotation->number).'.pdf';

        return $pdf->stream($filename);
    }
}
