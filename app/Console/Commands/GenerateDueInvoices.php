<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Langganan;
use App\Models\Invoice;
use App\Models\Pelanggan;
use App\Events\InvoiceCreated;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class GenerateDueInvoices extends Command
{
    protected $signature = 'invoice:generate-due {--force : Force run regardless of date}';
    protected $description = 'Generate invoices for customers with due date today';

    public function handle()
    {
        $today = Carbon::now()->format('Y-m-d');
        $this->info("Memulai generate invoice untuk tanggal jatuh tempo: {$today}");
        
        // Sesuaikan query dengan struktur tabel yang benar
        $query = Langganan::query();
        
        if (!$this->option('force')) {
            $query->where('tgl_jatuh_tempo', $today);
        }
        
        $langgananJatuhTempo = $query->get();
        
        $this->info("Ditemukan {$langgananJatuhTempo->count()} pelanggan jatuh tempo.");
        
        if ($langgananJatuhTempo->isEmpty()) {
            $this->info("Tidak ada invoice yang perlu dibuat.");
            return 0;
        }
        
        $successCount = 0;
        $failCount = 0;
        
        foreach ($langgananJatuhTempo as $langganan) {
            try {
                // Cek apakah sudah ada invoice untuk bulan ini
                $existingInvoice = Invoice::where('pelanggan_id', $langganan->pelanggan_id)
                    ->whereMonth('tgl_invoice', Carbon::now()->month)
                    ->whereYear('tgl_invoice', Carbon::now()->year)
                    ->exists();
                
                if ($existingInvoice && !$this->option('force')) {
                    $this->info("Invoice untuk pelanggan ID: {$langganan->pelanggan_id} bulan ini sudah ada. Dilewati.");
                    continue;
                }
                
                // Menggunakan transaction untuk menghindari race condition
                DB::transaction(function() use ($langganan, &$successCount) {
                    // Generate invoice number
                    $invoiceNumber = 'INV-' . date('Ymd') . '-' . strtoupper(Str::random(5));
                    
                    // Ambil informasi pelanggan
                    $pelanggan = Pelanggan::find($langganan->pelanggan_id);
                    
                    if (!$pelanggan) {
                        throw new \Exception("Pelanggan dengan ID {$langganan->pelanggan_id} tidak ditemukan");
                    }
                    
                    // Buat invoice
                    $invoice = new Invoice();
                    $invoice->pelanggan_id = $langganan->pelanggan_id;
                    $invoice->invoice_number = $invoiceNumber;
                    $invoice->tgl_invoice = Carbon::now();
                    $invoice->tgl_jatuh_tempo = Carbon::now()->addDays(7);
                    $invoice->total_harga = $langganan->total_harga_layanan_pajak;
                    $invoice->email = $pelanggan->email;
                    $invoice->no_telp = $pelanggan->no_telp;
                    $invoice->brand = $langganan->id_brand;
                    $invoice->status_invoice = 'Menunggu Pembayaran';
                    $invoice->id_pelanggan = $pelanggan->id_pelanggan;
                    $invoice->save();
                    
                    // Log untuk debugging
                    Log::info('Creating Invoice: ', $invoice->toArray());
                    
                    // Log invoice setelah dibuat
                    Log::info('Invoice Created: ', $invoice->toArray());
                    
                    // Dispatch event untuk memproses dengan Xendit
                    // PENTING: Jangan panggil XenditService langsung, gunakan event
                    event(new InvoiceCreated($invoice));
                    
                    $this->info("✓ Berhasil membuat invoice {$invoiceNumber} untuk pelanggan ID: {$langganan->pelanggan_id}");
                    $successCount++;
                });
                
            } catch (\Exception $e) {
                $this->error("✗ Error: {$e->getMessage()}");
                Log::error('Error generate invoice', [
                    'langganan_id' => $langganan->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $failCount++;
            }
        }
        
        $this->info("Proses selesai: {$successCount} berhasil, {$failCount} gagal.");
        return 0;
    }
}