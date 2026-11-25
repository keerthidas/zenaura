<?php

namespace App\Http\Controllers;

use App\Models\Quotation;
use App\Support\QuotationExcelExporter;
use Illuminate\Http\Request;

class QuotationExcelController extends Controller
{
    public function __invoke(Request $request, Quotation $quotation, QuotationExcelExporter $exporter)
    {
        return $exporter->download(
            $quotation,
            $request->query('discount') === 'hide',
        );
    }
}
