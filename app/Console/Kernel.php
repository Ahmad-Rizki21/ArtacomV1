<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Models\Langganan;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
       // \App\Console\Commands\CheckDueDateCommand::class,
    ];

    /**
     * Define the application's command schedule.
     */
   protected function schedule(Schedule $schedule): void
{
    // =================================================================
    // JADWAL HARIAN (TIDAK BERUBAH)
    // =================================================================
    $schedule->command('invoice:generate-due --days=5')->dailyAt('03:00');

    // =================================================================
    // PERINTAH MASTER TUNGGAL (SETIAP 5 MENIT)
    // =================================================================
    // HANYA INI yang menjalankan semua tugas terkait MikroTik
    $schedule->command('mikrotik:run-all-tasks')
             ->everyFiveMinutes()
             ->withoutOverlapping(15)
             ->appendOutputTo(storage_path('logs/mikrotik-master-runner.log'));

    // =================================================================
    // QUEUE WORKER (TETAP DIPERLUKAN)
    // =================================================================
    // Perintah ini penting untuk memproses jobs, biarkan aktif.
    $schedule->command('queue:work --stop-when-empty --tries=3')
             ->everyFiveMinutes()
             ->withoutOverlapping();
}

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
