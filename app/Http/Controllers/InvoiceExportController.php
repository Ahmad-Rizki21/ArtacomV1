<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exports\InvoicesExport;
use Maatwebsite\Excel\Facades\Excel;

class InvoiceExportController extends Controller
{
    public function export()
    {
        return Excel::download(new InvoicesExport, 'invoices.xlsx');
    }
}