<?php

namespace App\Services;

use App\Models\Langganan;
use App\Models\Invoice;
use App\Models\Pelanggan;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class InvoiceService 
{
    protected $xenditService;

    public function __construct(XenditService $xenditService)
    {
        $this->xenditService = $xenditService;
    }

    public function buatInvoiceSebelumJatuhTempo()
    {
        // Ambil langganan yang akan jatuh tempo dalam 5 hari
        $langgananAkanJatuhTempo = Langganan::where('tgl_jatuh_tempo', '<=', now()->addDays(5))
            ->where('tgl_jatuh_tempo', '>', now())
            ->get();

        foreach ($langgananAkanJatuhTempo as $langganan) {
            // Cek apakah sudah ada invoice untuk periode ini
            $invoiceExisting = Invoice::where('pelanggan_id', $langganan->pelanggan_id)
                ->whereMonth('tgl_invoice', now()->month)
                ->whereYear('tgl_invoice', now()->year)
                ->exists();

            if (!$invoiceExisting) {
                // Buat invoice baru
                $invoice = Invoice::create([
                    'pelanggan_id' => $langganan->pelanggan_id,
                    'total_harga' => $langganan->total_harga_layanan_x_pajak,
                    'tgl_invoice' => now(),
                    'tgl_jatuh_tempo' => $langganan->tgl_jatuh_tempo,
                    'status_invoice' => 'Menunggu Pembayaran',
                    'description' => 'Tagihan bulan ' . now()->format('F Y'),
                    'brand' => $langganan->id_brand
                ]);

                // Proses integrasi dengan Xendit menggunakan method createInvoice
                $this->prosesInvoiceKeXendit($invoice);
            }
        }
    }

    private function prosesInvoiceKeXendit(Invoice $invoice)
    {
        try {
            // Gunakan method createInvoice dari XenditService
            $hasilXendit = $this->xenditService->createInvoice($invoice);

            // Cek apakah proses berhasil
            if ($hasilXendit['status'] === 'success') {
                Log::info('Invoice berhasil dibuat di Xendit', [
                    'invoice_number' => $invoice->invoice_number,
                    'xendit_id' => $hasilXendit['xendit_id']
                ]);
            } else {
                Log::error('Gagal membuat invoice di Xendit', [
                    'invoice_number' => $invoice->invoice_number,
                    'error' => $hasilXendit['error'] ?? 'Unknown error'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Gagal memproses invoice ke Xendit', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
}