<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Langganan;
use App\Models\Invoice;
use App\Events\InvoiceCreated;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class GenerateDueInvoices extends Command
{
    protected $signature = 'invoice:generate-due {--force : Force run regardless of date} {--days=5 : Days before due date to generate invoice}';
    protected $description = 'Generate invoices for customers with upcoming due date';

    public function handle()
    {
        $daysBeforeDue = intval($this->option('days'));
        $targetDate = Carbon::now()->addDays($daysBeforeDue)->format('Y-m-d');
        
        $this->info("Memulai generate invoice untuk pelanggan dengan tanggal jatuh tempo: {$targetDate}");
        
        $query = Langganan::query();
        
        if (!$this->option('force')) {
            $query->where('tgl_jatuh_tempo', $targetDate);
        }
        
        // REVISI 1: Eager load semua relasi yang dibutuhkan di awal
        $langgananJatuhTempo = $query->with(['pelanggan.dataTeknis', 'invoices'])->get();
        
        $this->info("Ditemukan {$langgananJatuhTempo->count()} pelanggan yang akan jatuh tempo.");
        
        if ($langgananJatuhTempo->isEmpty()) {
            $this->info("Tidak ada invoice yang perlu dibuat.");
            return self::SUCCESS;
        }
        
        $successCount = 0;
        $failCount = 0;
        
        foreach ($langgananJatuhTempo as $langganan) {
            try {
                // REVISI 2: Pengecekan invoice dilakukan pada data yang sudah dimuat
                $existingInvoice = $langganan->invoices
                    ->where('tgl_jatuh_tempo', $langganan->tgl_jatuh_tempo)
                    ->isNotEmpty();

                if ($existingInvoice) {
                    $this->info("Invoice sudah ada untuk pelanggan {$langganan->pelanggan_id}, dilewati.");
                    continue;
                }

                // REVISI 3: Data pelanggan sudah tersedia
                $pelanggan = $langganan->pelanggan;

                if (!$pelanggan) {
                    throw new \Exception("Data pelanggan tidak ditemukan untuk langganan ID: {$langganan->id}");
                }

                DB::transaction(function () use ($langganan, $pelanggan, &$successCount) {
                    $invoice = new Invoice();
                    $invoice->pelanggan_id = $langganan->pelanggan_id;
                    $invoice->invoice_number = 'INV-' . now()->format('Ymd') . '-' . rand(1000, 9999);
                    $invoice->tgl_invoice = now();
                    $invoice->tgl_jatuh_tempo = $langganan->tgl_jatuh_tempo;
                    $invoice->total_harga = $langganan->total_harga_layanan_x_pajak; 
                    $invoice->email = $pelanggan->email;
                    $invoice->no_telp = $pelanggan->no_telp;
                    $invoice->brand = $langganan->id_brand;
                    $invoice->status_invoice = 'Menunggu Pembayaran';
                    // REVISI 4: Akses dataTeknis yang sudah di-load dengan aman
                    $invoice->id_pelanggan = $pelanggan->dataTeknis->id_pelanggan ?? null; 
                    $invoice->save();

                    event(new InvoiceCreated($invoice));

                    $this->info("Invoice berhasil dibuat: {$invoice->invoice_number}");
                    $successCount++;
                });

            } catch (\Exception $e) {
                $failCount++;
                $this->error("Error pada langganan ID {$langganan->id}: " . $e->getMessage());
                Log::error('Generate invoice error', ['langganan_id' => $langganan->id, 'error' => $e->getMessage()]);
            }
        }
        
        $this->info("Proses selesai: {$successCount} berhasil, {$failCount} gagal.");
        return self::SUCCESS;
    }
}
