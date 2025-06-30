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
        \App\Console\Commands\CheckDueDateCommand::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // =================================================================
        // JADWAL UTAMA
        // =================================================================

        // 1. Generate Invoice: Dijalankan sekali sehari.
        // WAKTU: '03:00' UTC adalah jam 10:00 pagi WIB (GMT+7).
        // Sesuaikan '03:00' jika jam target Anda berbeda.
        $schedule->command('invoice:generate-due --days=5')
            ->dailyAt('03:00') // Diubah dari '10:00' ke '03:00' target 10:00 WIB
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/invoice-scheduler.log'));

        // 2. Cek Langganan Overdue: Dijalankan setiap 15 menit.
        $schedule->command('app:check-overdue-subscriptions')
            ->everyFifteenMinutes()
            ->appendOutputTo(storage_path('logs/subscription-checks.log'));

        // 3. Cek Status Pembayaran Invoice: Dijalankan setiap 15 menit.
        $schedule->command('invoice:check-paid-status')
            ->everyFifteenMinutes()
            ->appendOutputTo(storage_path('logs/invoice-paid-status.log'));

        // =================================================================
        // JADWAL PEMELIHARAAN (Berjalan setiap 5 menit)
        // =================================================================

        // Daftar perintah yang berjalan setiap 5 menit
        $fiveMinuteCommands = [
            'check:due-date'           => 'logs/due-date-check.log',
            'app:sync-mikrotik-status' => 'logs/mikrotik-sync.log',
            'monitor:mikrotik'         => 'logs/mikrotik-monitor.log',
            'server:capture-status'    => 'logs/server-status.log',
            'mikrotik:create-secret'   => 'logs/mikrotik-create-secret.log',
        ];

        foreach ($fiveMinuteCommands as $command => $logFile) {
            $schedule->command($command)
                ->everyFiveMinutes()
                ->appendOutputTo(storage_path($logFile));
        }
        
        // Menjalankan fungsi pengecekan status langganan internal setiap 5 menit
        $schedule->call(function () {
            Langganan::checkAllSubscriptionStatus();
        })->everyFiveMinutes()->appendOutputTo(storage_path('logs/langganan-status-check.log'));

        // Menjalankan queue worker setiap 5 menit untuk memproses job
        $schedule->command('queue:work --stop-when-empty --tries=3')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/queue-worker.log'));
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
