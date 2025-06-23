<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schedule;
use App\Services\GeneratorDueInvoices;
use App\Models\Langganan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Command untuk testing invoice generator pada tanggal tertentu
// Perbaikan parameter nullable dengan syntax PHP 8
// Artisan::command('invoice:test-generate {date?}', function (?string $date = null) {
//     $targetDate = $date ? Carbon::parse($date) : Carbon::now();
    
//     $this->comment("Testing invoice generator untuk tanggal: " . $targetDate->format('Y-m-d'));
    
//     // Override tanggal jatuh tempo untuk testing dengan opsi --force
//     $this->call('invoice:generate-due', [
//         '--force' => true
//     ]);
    
//     $this->comment("Invoice generator telah dijalankan dengan mode force.");
    
// })->purpose('Test generate invoice for a specific date');

// Command untuk memaksa generate invoice (untuk testing cepat)
// Artisan::command('invoice:force-generate', function () {
//     $this->info('Memaksa generate invoice untuk semua pelanggan aktif tanpa melihat tanggal jatuh tempo...');
//     $this->call('invoice:generate-due', [
//         '--force' => true
//     ]);
// })->purpose('Force generate invoices for all active customers');


// Command untuk menjalankan invoice
// Schedule::call(new GeneratorDueInvoices)->everyMinute();
// Schedule::command('invoice:generate-due')->everyFiveMinutes(); //tiap jam 10 pagi
// Schedule::command('app:check-overdue-subscriptions')->everyFiveMinutes(); // tiap 30 Menit
// Schedule::command('app:sync-mikrotik')->everyFiveMinutes();
// Schedule::command('invoice:check-paid-status')->everyFiveMinutes(); // tiap 1 Menit
// Schedule::command('monitor:mikrotik')->everyFiveMinutes(); // tiap 1 Menit


// Schedule::call(function () {
//     Langganan::checkAllSubscriptionStatus();
// })->everyMinute();