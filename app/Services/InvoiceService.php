<?php

namespace App\Services;

use App\Models\Langganan;
use App\Models\Invoice;
use App\Models\Pelanggan;
use App\Events\InvoiceCreated;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class InvoiceService 
{
    protected $xenditService;
    
    public function __construct(XenditService $xenditService = null)
    {
        $this->xenditService = $xenditService;
    }

   /**
 * Buat invoice untuk langganan yang akan jatuh tempo dalam beberapa hari
 * 
 * @param int $daysBeforeDue Jumlah hari sebelum jatuh tempo
 * @param bool $force Paksa pembuatan meskipun sudah ada invoice
 * @return array Statistik hasil proses
 */
public function buatInvoiceSebelumJatuhTempo($daysBeforeDue = 5, $force = false, $testDate = null)
{
    $stats = [
        'processed' => 0,
        'created' => 0,
        'skipped' => 0,
        'errors' => 0
    ];

    // Gunakan tanggal simulasi untuk pengujian
    $now = $testDate ? Carbon::parse($testDate) : Carbon::now();
    $targetDate = $now->addDays($daysBeforeDue)->format('Y-m-d');
    Log::info("Memulai generate invoice untuk pelanggan dengan tanggal jatuh tempo: {$targetDate} (Simulasi: {$testDate})");

    $query = Langganan::query();
    if (!$force) {
        $query->where('tgl_jatuh_tempo', $targetDate)
              ->where('user_status', 'Aktif');
    }

    $langgananJatuhTempo = $query->get();
    Log::info("Ditemukan {$langgananJatuhTempo->count()} pelanggan yang akan jatuh tempo {$daysBeforeDue} hari lagi.");

    if ($langgananJatuhTempo->isEmpty()) {
        return $stats;
    }

    foreach ($langgananJatuhTempo as $langganan) {
        $stats['processed']++;

        try {
            $existingInvoice = Invoice::where('pelanggan_id', $langganan->pelanggan_id)
                ->whereMonth('tgl_invoice', $now->month)
                ->whereYear('tgl_invoice', $now->year)
                ->exists();

            if ($existingInvoice && !$force) {
                Log::info("Invoice untuk pelanggan ID: {$langganan->pelanggan_id} bulan ini sudah ada. Dilewati.");
                $stats['skipped']++;
                continue;
            }

            DB::transaction(function () use ($langganan, &$stats, $targetDate, $now) {
                $invoiceNumber = 'INV-' . $now->format('Ymd') . '-' . rand(1000, 9999);
                $pelanggan = Pelanggan::find($langganan->pelanggan_id);

                if (!$pelanggan) {
                    throw new \Exception("Pelanggan dengan ID {$langganan->pelanggan_id} tidak ditemukan");
                }

                $dueDate = Carbon::parse($langganan->tgl_jatuh_tempo);
                $daysInMonth = $dueDate->daysInMonth;
                $daysRemaining = $dueDate->diffInDays($now->startOfMonth());
                $totalHarga = $langganan->total_harga_layanan_x_pajak;
                
                // Cek apakah perlu prorate
                $isProrate = false;
                $prorateAmount = $totalHarga;
                
                // Hitung prorate jika bukan awal bulan atau ada ketentuan khusus
                if ($now->day > 1) {
                    $isProrate = true;
                    $prorateAmount = ($totalHarga / $daysInMonth) * $daysRemaining;
                    $prorateAmount = ceil($prorateAmount / 1000) * 1000; // Pembulatan ke atas kelipatan 1000
                }

                $invoice = new Invoice();
                $invoice->pelanggan_id = $langganan->pelanggan_id;
                $invoice->invoice_number = $invoiceNumber;
                $invoice->tgl_invoice = $now; // Gunakan tanggal simulasi
                $invoice->tgl_jatuh_tempo = $dueDate;
                $invoice->total_harga = $isProrate ? $prorateAmount : $totalHarga;
                $invoice->email = $pelanggan->email;
                $invoice->no_telp = $pelanggan->no_telp;
                $invoice->brand = $langganan->id_brand;
                $invoice->status_invoice = 'Menunggu Pembayaran';
                $invoice->id_pelanggan = $pelanggan->id_pelanggan;
                
                // Tambahkan keterangan jika prorate
                if ($isProrate) {
                    $invoice->description = "Biaya prorate berlangganan internet"; 
                }
                
                $invoice->save();

                Log::info('Invoice Created: ', $invoice->toArray());

                if ($invoice->status_invoice === 'Lunas') {
                    $newDueDate = $dueDate->addMonth()->day(1);
                    $langganan->tgl_jatuh_tempo = $newDueDate->format('Y-m-d');
                    $langganan->user_status = 'Aktif';
                    $langganan->save();
                    Log::info('Updated due date and status after payment', [
                        'pelanggan_id' => $langganan->pelanggan_id,
                        'new_due_date' => $langganan->tgl_jatuh_tempo,
                        'new_status' => 'Aktif'
                    ]);
                }

                if (class_exists('App\Events\InvoiceCreated')) {
                    event(new InvoiceCreated($invoice));
                } elseif ($this->xenditService) {
                    $this->prosesInvoiceKeXendit($invoice);
                }

                $stats['created']++;
            });
        } catch (\Exception $e) {
            $stats['errors']++;
            Log::error('Error generate invoice', [
                'langganan_id' => $langganan->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    Log::info("Proses pembuatan invoice selesai", $stats);
    return $stats;
}

    /**
     * Proses invoice ke Xendit menggunakan XenditService
     * 
     * @param Invoice $invoice
     * @return array|null
     */
    private function prosesInvoiceKeXendit(Invoice $invoice)
    {
        if (!$this->xenditService) {
            Log::warning('XenditService tidak tersedia untuk memproses invoice', [
                'invoice_id' => $invoice->id
            ]);
            return null;
        }
        
        try {
            // Gunakan method createInvoice dari XenditService
            $hasilXendit = $this->xenditService->createInvoice($invoice);

            // Cek apakah proses berhasil
            if ($hasilXendit['status'] === 'success') {
                // Update invoice dengan data dari Xendit
                $invoice->update([
                    'xendit_id' => $hasilXendit['xendit_id'] ?? null,
                    'xendit_url' => $hasilXendit['invoice_url'] ?? null,
                    'xendit_status' => 'PENDING'
                ]);
                
                Log::info('Invoice berhasil dibuat di Xendit', [
                    'invoice_number' => $invoice->invoice_number,
                    'xendit_id' => $hasilXendit['xendit_id'] ?? null
                ]);
                
                return $hasilXendit;
            } else {
                Log::error('Gagal membuat invoice di Xendit', [
                    'invoice_number' => $invoice->invoice_number,
                    'error' => $hasilXendit['error'] ?? 'Unknown error'
                ]);
                
                return $hasilXendit;
            }
        } catch (\Exception $e) {
            Log::error('Gagal memproses invoice ke Xendit', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }
}