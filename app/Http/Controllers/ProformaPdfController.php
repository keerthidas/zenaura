<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Models\ProformaInvoice;
use App\Models\Quotation;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class ProformaPdfController extends Controller
{
    public function __invoke(Quotation $quotation, ProformaInvoice $proforma): Response
    {
        abort_unless($proforma->quotation_id === $quotation->id, 404);

        $quotation->load(['client', 'proformas']);
        $settings = CompanySetting::current();
        $logoDataUri = pdf_brand_data_uri($settings->logo_path, 'images/logo.png');
        $signatureDataUri = pdf_brand_data_uri($settings->signature_path, 'images/signature.png');
        $stampDataUri = pdf_brand_data_uri($settings->stamp_path, 'images/costamp.png');

        $pdf = Pdf::loadView('pdf.proforma', [
            'quotation' => $quotation,
            'proforma' => $proforma,
            'settings' => $settings,
            'logoDataUri' => $logoDataUri,
            'signatureDataUri' => $signatureDataUri,
            'stampDataUri' => $stampDataUri,
        ])->setPaper('a4', 'portrait');

        $filename = str_replace(['/', '\\'], '-', $proforma->number).'.pdf';

        return $pdf->stream($filename);
    }
}
