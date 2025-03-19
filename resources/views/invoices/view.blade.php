@extends('layouts.app')

@section('title', 'Invoice #' . $invoice->invoice_number)

@section('styles')
<style>
    /* Styling untuk tampilan invoice */
    .invoice-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 20px;
        background-color: #0f1421;
        color: white;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    
    .invoice-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 30px;
    }
    
    .invoice-logo {
        background-color: #4b1d83;
        width: 50px;
        height: 50px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .invoice-info {
        display: flex;
        justify-content: space-between;
        margin-bottom: 30px;
    }
    
    .invoice-details {
        text-align: right;
    }
    
    .invoice-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 30px;
    }
    
    .invoice-table th, .invoice-table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #2e3446;
    }
    
    .invoice-table th {
        background-color: #1a2035;
    }
    
    .invoice-totals {
        display: flex;
        justify-content: flex-end;
    }
    
    .invoice-totals table {
        width: 300px;
    }
    
    .invoice-totals td {
        padding: 8px;
    }
    
    .invoice-totals td:last-child {
        text-align: right;
    }
    
    .signature-section, .notes-section {
        margin-top: 30px;
    }
    
    .btn-action {
        padding: 8px 16px;
        margin-left: 10px;
        border-radius: 4px;
        border: none;
        color: white;
        text-decoration: none;
        display: inline-block;
    }
    
    .btn-print {
        background-color: #26a0fc;
    }
    
    .btn-back {
        background-color: #6c757d;
    }
</style>
@endsection

@section('content')
<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ url()->previous() }}" class="btn-action btn-back">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
        <div>
            <a href="{{ route('invoice.print', $invoice->id) }}" class="btn-action btn-print" target="_blank">
                <i class="fas fa-print"></i> Print
            </a>
        </div>
    </div>
    
    <div class="invoice-container">
        <div class="invoice-header">
            <div class="invoice-logo">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                    <polyline points="2 17 12 22 22 17"></polyline>
                    <polyline points="2 12 12 17 22 12"></polyline>
                </svg>
            </div>
            <div>
                <h2>Invoice</h2>
                <p>#{{ $invoice->invoice_number }}</p>
            </div>
        </div>
        
        <div class="invoice-info">
            <div class="bill-from">
                <p><strong>Bill From:</strong></p>
                <h4>{{ optional($invoice->hargaLayanan)->brand ?? 'Your Company' }}</h4>
                <p>{{ $invoice->no_telp }}</p>
            </div>
            
            <div class="bill-to">
                <p><strong>Bill To:</strong></p>
                <h4>{{ optional($invoice->pelanggan)->nama ?? 'Client Name' }}</h4>
                <p>{{ $invoice->email }}</p>
                <p>{{ optional($invoice->pelanggan)->no_telp ?? $invoice->no_telp }}</p>
            </div>
            
            <div class="invoice-details">
                <table>
                    <tr>
                        <td>Issue Date:</td>
                        <td>{{ $invoice->tgl_invoice ? $invoice->tgl_invoice->format('Y-m-d') : 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td>Due Date:</td>
                        <td>{{ $invoice->tgl_jatuh_tempo ? $invoice->tgl_jatuh_tempo->format('Y-m-d') : 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td>Status:</td>
                        <td>{{ $invoice->status_invoice ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td>Type:</td>
                        <td>{{ $invoice->xendit_id ? 'Xendit Invoice' : 'Regular Invoice' }}</td>
                    </tr>
                </table>
            </div>
        </div>
        
        <table class="invoice-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong>{{ $invoice->description ?? 'Internet Service' }}</strong>
                        <p>{{ optional($invoice->langganan)->layanan ?? 'Internet Package' }}</p>
                    </td>
                    <td>
                        <table>
                            <tr>
                                <td>Price:</td>
                                <td>{{ number_format($invoice->total_harga, 2) }} IDR</td>
                            </tr>
                            <tr>
                                <td>TAX</td>
                                <td>11 %</td>
                            </tr>
                           
                            <tr>
                                <td>QTY:</td>
                                <td>1</td>
                            </tr>
                            <tr>
                                <td>Total:</td>
                                <td>{{ number_format($invoice->total_harga, 2) }} IDR</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <div class="invoice-totals">
            <table>
                <tr>
                    <td>Sub Total</td>
                    <td>{{ number_format($invoice->total_harga, 2) }} IDR</td>
                </tr>
                <tr>
                    <td>Tax</td>
                    <td>11 %</td>
                </tr>
                
                <tr>
                    <td>Paid</td>
                    <td>{{ $invoice->paid_amount ? number_format($invoice->paid_amount, 2) : '0.00' }} IDR</td>
                </tr>
                <!-- <tr>
                    <td><strong>Balance Due</strong></td>
                    <td><strong>{{ number_format($invoice->total_harga - ($invoice->paid_amount ?? 0), 2) }} IDR</strong></td>
                </tr> -->
            </table>
        </div>
        
        <div class="signature-section">
            <h4>Signature</h4>
            <p>{{ optional($invoice->hargaLayanan)->brand ?? 'Your Company' }}</p>
            <!-- <p>support@{{ strtolower(optional($invoice->hargaLayanan)->brand ?? 'yourcompany') }}.com</p> -->
            <p>{{ $invoice->no_telp }}</p>
        </div>
        
        <div class="notes-section">
            <h4>Notes</h4>
            <p>{{ $invoice->description ?? 'Thank you for your payment.' }}</p>
        </div>
    </div>
</div>
@endsection