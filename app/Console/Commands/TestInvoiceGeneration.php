<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\InvoiceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TestInvoiceGeneration extends Command
{
    protected $signature = 'test:invoice-generation {virtual_date? : Tanggal virtual (format: Y-m-d)} {--days=5 : Hari sebelum jatuh tempo}';
    protected $description = 'Pengujian generate invoice dengan tanggal virtual';

    public function handle()
    {
        // Simpan waktu asli
        $realNow = now();
        
        // Ambil tanggal virtual dari parameter, default ke 26 Mei 2025
        $virtualDate = $this->argument('virtual_date') ?? '2025-05-26';
        $daysBeforeDue = $this->option('days');
        
        // Tampilkan informasi pengujian
        $this->info('Pengujian pembuatan invoice dengan waktu virtual:');
        $this->info("- Tanggal virtual: $virtualDate");
        $this->info("- Hari sebelum jatuh tempo: $daysBeforeDue");
        
        try {
            // Set waktu virtual menggunakan Carbon::setTestNow()
            Carbon::setTestNow(Carbon::parse($virtualDate));
            
            // Konfirmasi waktu yang digunakan
            $this->info('Waktu sistem sekarang (virtual): ' . now()->format('Y-m-d H:i:s'));
            $this->info('Target tanggal jatuh tempo: ' . now()->addDays($daysBeforeDue)->format('Y-m-d'));
            
            // Panggil generate-due command dengan exec
            $this->info("\nMenjalankan invoice:generate-due...");
            $this->call('invoice:generate-due', [
                '--days' => $daysBeforeDue
            ]);
            
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            Log::error('Error testing invoice generation: ' . $e->getMessage(), [
                'virtual_date' => $virtualDate,
                'trace' => $e->getTraceAsString()
            ]);
        } finally {
            // Reset waktu kembali ke normal
            Carbon::setTestNow($realNow);
            $this->info("\nWaktu sistem dikembalikan ke: " . now()->format('Y-m-d H:i:s'));
        }

        return 0;
    }
}