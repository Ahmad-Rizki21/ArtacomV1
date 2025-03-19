<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $invoice->invoice_number }} - Print</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: white;
            color: black;
        }
        
        .print-invoice {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
        }
        
        .invoice-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        
        .invoice-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        .invoice-table th, .invoice-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .invoice-totals {
            margin-left: auto;
            width: 300px;
        }
        
        .signature-section, .notes-section {
            margin-top: 30px;
        }
        
        .no-print {
            margin-bottom: 20px;
            text-align: center;
        }
        
        .btn {
            padding: 8px 16px;
            margin: 0 5px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="btn" onclick="window.print()">Print Invoice</button>
        <button class="btn" onclick="window.close()">Close</button>
    </div>
    
    <div class="print-invoice">
        <div class="invoice-header">
            <div>
                <h2>{{ optional($invoice->hargaLayanan)->brand ?? 'Your Company' }}</h2>
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
                                <td></td>TAX</td>
                                <td>11 %</td>
                            </tr>
                            <!-- <tr>
                                <td>Discount:</td>
                                <td>0.00 IDR</td>
                            </tr> -->
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
                <!-- <tr>
                    <td>Discount</td>
                    <td>0.00 IDR</td>
                </tr> -->
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
            <p>{{ $invoice->no_telp }}</p>
        </div>
        
        <div class="notes-section">
            <h4>Notes</h4>
            <p>{{ $invoice->description ?? 'Thank you for your payment.' }}</p>
        </div>
    </div>
    
    <script>
        // Auto print saat halaman dimuat
        window.onload = function() {
            setTimeout(function() {
                // window.print();
            }, 500);
        };
    </script>
</body>
</html>