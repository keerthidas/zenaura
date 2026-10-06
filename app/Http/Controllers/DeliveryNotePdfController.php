<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Models\DeliveryNote;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class DeliveryNotePdfController extends Controller
{
    public function __invoke(DeliveryNote $deliveryNote): Response
    {
        $deliveryNote->load(['quotation.client', 'supplier', 'items']);
        $settings = CompanySetting::current();

        $pdf = Pdf::loadView('pdf.delivery-note', [
            'deliveryNote' => $deliveryNote,
            'settings' => $settings,
            'logoDataUri' => pdf_brand_data_uri($settings->logo_path, 'images/logo.png'),
            'signatureDataUri' => pdf_brand_data_uri($settings->signature_path, 'images/signature.png'),
            'stampDataUri' => pdf_brand_data_uri($settings->stamp_path, 'images/costamp.png'),
        ])->setPaper('a4', 'portrait');

        return $pdf->stream(str_replace(['/', '\\'], '-', $deliveryNote->number).'.pdf');
    }
}