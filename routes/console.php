<?php

use Illuminate\Support\Facades\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


// =================================================================
// PENJADWALAN FINAL YANG KOMPATIBEL
// =================================================================

// TUGAS 1: Menjalankan semua tugas penting terkait Mikrotik
Schedule::command('mikrotik:run-all-tasks')
        //  ->everyFifteenMinutes()
        ->cron('*/20 * * * *')
        ->withoutOverlapping(15)
         ->appendOutputTo(storage_path('logs/mikrotik-runner.log'));
         // Menggunakan onFailure versi sederhana yang paling kompatibel


// TUGAS 2: Generate invoice untuk yang akan jatuh tempo
Schedule::command('invoice:generate-due --days=5')->dailyAt('03:00');

// TUGAS 3: Menjalankan queue worker
Schedule::command('queue:work --stop-when-empty --tries=3')
         ->everyFiveMinutes()
         ->withoutOverlapping();