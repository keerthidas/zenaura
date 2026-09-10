<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Models\Quotation;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class InvoicePdfController extends Controller
{
    public function __invoke(Quotation $quotation): Response
    {
        abort_unless($quotation->isAccepted(), 404);

        $quotation->ensureInvoiceNumber();
        $quotation->load(['client', 'items']);
        $settings = CompanySetting::current();
        $logoDataUri = pdf_public_data_uri('images/logo.png') ?: pdf_data_uri($settings->logo_path);
        $signatureDataUri = pdf_public_data_uri('images/signature.png');
        $stampDataUri = pdf_public_data_uri('images/costamp.png');

        $pdf = Pdf::loadView('pdf.invoice', [
            'quotation' => $quotation,
            'settings' => $settings,
            'logoDataUri' => $logoDataUri,
            'signatureDataUri' => $signatureDataUri,
            'stampDataUri' => $stampDataUri,
        ])->setPaper('a4', 'portrait');

        $filename = str_replace(['/', '\\'], '-', $quotation->invoice_number).'.pdf';

        return $pdf->stream($filename);
    }
}
