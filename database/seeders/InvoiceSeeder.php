<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Invoice;

class InvoiceSeeder extends Seeder
{
    public function run()
    {
        Invoice::create([
            'invoice_number' => 'INV-20250223-0001',
            'pelanggan_id' => 1,
            'id_pelanggan' => 'pgb-sanjaya-10',
            'brand' => 'Jelantik',
            'total_harga' => 229900.00,
            'no_telp' => '62812859606822',
            'email' => 'sanjayadony13@gmail.com',
            'tgl_invoice' => '2025-02-23',
            'tgl_jatuh_tempo' => '2025-03-08',
            'payment_link' => 'https://xendit.co/example-invoice-link',
            'status_invoice' => 'Belum Dibayar',
        ]);
    }
}
