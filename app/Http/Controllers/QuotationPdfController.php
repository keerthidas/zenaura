<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Models\Quotation;
use App\Support\QuotationPdfMerger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class QuotationPdfController extends Controller
{
    public function __invoke(Request $request, Quotation $quotation): Response
    {
        $quotation->load(['client', 'items.product.images']);
        $settings = CompanySetting::current();
        $logoDataUri = pdf_brand_data_uri($settings->logo_path, 'images/logo.png');
        $itemImages = [];

        foreach ($quotation->items as $item) {
            $path = $item->product?->images->first()?->path;
            if ($path) {
                $itemImages[$item->id] = pdf_data_uri($path);
            }
        }

        $quotationPdf = Pdf::loadView('pdf.quotation', [
            'quotation' => $quotation,
            'settings' => $settings,
            'logoDataUri' => $logoDataUri,
            'itemImages' => $itemImages,
            'hideDiscount' => $request->query('discount') === 'hide',
        ])->setPaper('a4', 'portrait');

        $filename = str_replace(['/', '\\'], '-', $quotation->number).'.pdf';
        $pdf = app(QuotationPdfMerger::class)->append(
            $quotationPdf->output(),
            resource_path('pdf/brand-board.pdf'),
        );

        return response($pdf, Response::HTTP_OK, [
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Content-Type' => 'application/pdf',
        ]);
    }
}
