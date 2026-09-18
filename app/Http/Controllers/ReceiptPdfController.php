<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Models\ProformaInvoice;
use App\Models\Quotation;
use App\Models\ReceiptVoucher;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class ReceiptPdfController extends Controller
{
    public function __invoke(Quotation $quotation, ProformaInvoice $proforma, ReceiptVoucher $receipt): Response
    {
        abort_unless($proforma->quotation_id === $quotation->id, 404);
        abort_unless($receipt->proforma_invoice_id === $proforma->id, 404);

        $quotation->load('client');
        $proforma->setRelation('quotation', $quotation);
        $settings = CompanySetting::current();

        $pdf = Pdf::loadView('pdf.receipt', [
            'quotation' => $quotation,
            'proforma' => $proforma,
            'receipt' => $receipt,
            'settings' => $settings,
            'logoDataUri' => pdf_brand_data_uri($settings->logo_path, 'images/logo.png'),
            'signatureDataUri' => pdf_brand_data_uri($settings->signature_path, 'images/signature.png'),
            'stampDataUri' => pdf_brand_data_uri($settings->stamp_path, 'images/costamp.png'),
        ])->setPaper('a4', 'portrait');

        $filename = str_replace(['/', '\\'], '-', $receipt->number).'.pdf';

        return $pdf->stream($filename);
    }
}
