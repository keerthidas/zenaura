<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Models\PurchaseOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class PurchaseOrderPdfController extends Controller
{
    public function __invoke(PurchaseOrder $purchaseOrder): Response
    {
        $purchaseOrder->load(['quotation', 'supplier', 'items']);
        $settings = CompanySetting::current();

        $pdf = Pdf::loadView('pdf.purchase-order', [
            'order' => $purchaseOrder,
            'settings' => $settings,
            'logoDataUri' => pdf_brand_data_uri($settings->logo_path, 'images/logo.png'),
            'signatureDataUri' => pdf_brand_data_uri($settings->signature_path, 'images/signature.png'),
            'stampDataUri' => pdf_brand_data_uri($settings->stamp_path, 'images/costamp.png'),
        ])->setPaper('a4', 'portrait');

        $filename = str_replace(['/', '\\'], '-', $purchaseOrder->number).'.pdf';

        return $pdf->stream($filename);
    }
}
