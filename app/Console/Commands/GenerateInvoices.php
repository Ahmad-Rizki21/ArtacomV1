<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Langganan;
use App\Models\Invoice;
use App\Services\XenditService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class GenerateInvoices extends Command
{
    /**
     * Nama perintah yang akan dipanggil di terminal
     *
     * @var string
     */
    protected $signature = 'invoices:generate';
    protected $description = 'Generate invoice otomatis berdasarkan tanggal jatuh tempo pelanggan';

    /**
     * Xendit Service Instance
     */
    protected $xenditService;

    /**
     * Konstruktor
     */
    public function __construct(XenditService $xenditService)
    {
        parent::__construct();
        $this->xenditService = $xenditService;
    }

    /**
     * Eksekusi perintah
     */

     public function handle()
     {
         Log::info("🔄 Menjalankan cron job untuk membuat invoice otomatis.");
     
         // Ambil semua pelanggan yang jatuh tempo hari ini
         $langgananJatuhTempo = Langganan::whereDate('tgl_jatuh_tempo', Carbon::today())->get();
     
         if ($langgananJatuhTempo->isEmpty()) {
             Log::info("✅ Tidak ada pelanggan yang jatuh tempo hari ini.");
             return;
         }
     
         foreach ($langgananJatuhTempo as $langganan) {
             // Cek apakah invoice sudah dibuat untuk pelanggan ini
             $existingInvoice = Invoice::where('pelanggan_id', $langganan->pelanggan_id)
                 ->whereMonth('tgl_invoice', now()->month)
                 ->whereYear('tgl_invoice', now()->year)
                 ->first();
             
             if ($existingInvoice) {
                 Log::info("⚠️ Invoice sudah dibuat sebelumnya untuk pelanggan ini: " . $langganan->pelanggan_id);
                 return;
             }
     
             // Buat invoice baru
             $invoice = Invoice::create([
                 'invoice_number' => 'INV-' . now()->format('Ymd') . '-' . rand(1000, 9999),
                 'pelanggan_id' => $langganan->pelanggan_id,
                 'total_harga' => $langganan->total_harga_layanan_x_pajak,
                 'tgl_invoice' => Carbon::today(),
                 'tgl_jatuh_tempo' => $langganan->tgl_jatuh_tempo,
                 'status_invoice' => 'Menunggu Pembayaran'
             ]);
     
             // Kirim ke Xendit untuk mendapatkan link pembayaran
             $xenditResult = $this->xenditService->createInvoice($invoice);
     
             if ($xenditResult['status'] === 'success') {
                 $invoice->update([
                     'payment_link' => $xenditResult['invoice_url'],
                     'xendit_id' => $xenditResult['xendit_id'],
                     'xendit_external_id' => $xenditResult['external_id']
                 ]);
             
                 Log::info("✅ Invoice berhasil dibuat dan dikirim ke Xendit", [
                     'invoice_number' => $invoice->invoice_number,
                     'payment_link' => $xenditResult['invoice_url']
                 ]);
             } else {
                 Log::error("❌ Gagal mendapatkan data lengkap dari Xendit!", [
                     'response' => $xenditResult
                 ]);
             }
         }
     }
     


}
