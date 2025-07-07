<?php

use Illuminate\Support\Facades\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


// =================================================================
// SOLUSI FINAL: Penjadwalan Ditetapkan di Sini
// =================================================================

// TUGAS 1: Menjalankan semua tugas penting terkait Mikrotik (setiap 5 menit)
Schedule::command('mikrotik:run-all-tasks')
         ->everyFiveMinutes()
         ->withoutOverlapping(15)
         ->appendOutputTo(storage_path('logs/mikrotik-runner.log'));

// TUGAS 2: Generate invoice untuk yang akan jatuh tempo (berjalan sekali sehari)
Schedule::command('invoice:generate-due --days=5')->dailyAt('03:00');

// TUGAS 3: Menjalankan queue worker (PENTING, JANGAN DIHAPUS)
Schedule::command('queue:work --stop-when-empty --tries=3')
         ->everyFiveMinutes()
         ->withoutOverlapping();

// Catatan: Perintah 'filament-excel:prune' sudah otomatis didaftarkan oleh paketnya,
// jadi tidak perlu didaftarkan lagi di sini untuk menghindari duplikasi jadwal.

