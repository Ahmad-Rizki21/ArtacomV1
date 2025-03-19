<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceViewController extends Controller
{
    public function show(Invoice $invoice)
    {
        // Load relasi yang dibutuhkan
        $invoice->load(['pelanggan', 'langganan', 'hargaLayanan']);
        
        return view('invoices.view', compact('invoice'));
    }
    
    public function print(Invoice $invoice)
    {
        // Load relasi yang dibutuhkan
        $invoice->load(['pelanggan', 'langganan', 'hargaLayanan']);
        
        return view('invoices.print', compact('invoice'));
    }
}