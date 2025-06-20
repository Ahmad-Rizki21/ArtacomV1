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
        \App\Console\Commands\CheckDueDateCommand::class, // Tambahkan command di sini
    ];


    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        
        // Untuk development - jalankan setiap menit
        $schedule->command('invoice:generate-due --days=5')
        ->everyFiveMinutes()
        ->appendOutputTo(storage_path('logs/invoice-scheduler.log'));

        $schedule->command('app:check-overdue-subscriptions')
        ->everyFiveMinutes()       
        //  ->daily()
        //  ->at('00:01')
                 ->appendOutputTo(storage_path('logs/subscription-checks.log'));

        $schedule->command('check:due-date')
        ->everyFiveMinutes()
        ->appendOutputTo(storage_path('logs/due-date-check.log'));

        $schedule->command('app:sync-mikrotik-status')
        ->everyFiveMinutes()
        ->appendOutputTo(storage_path('logs/mikrotik-sync.log'));

        $schedule->call(function () {
            Langganan::checkAllSubscriptionStatus();
        })->everyFiveMinutes();

        $schedule->command('invoice:check-paid-status')->everyFiveMinutes();
        $schedule->command('monitor:mikrotik')->everyFiveMinutes();

        $schedule->command('queue:work --stop-when-empty --tries=3')
         ->everyFiveMinutes()
         ->withoutOverlapping()
         ->appendOutputTo(storage_path('logs/queue-worker.log'));


         $schedule->command('server:capture-status')
         ->everyFiveMinute() // atau sesuai kebutuhan, misalnya ->everyFiveMinutes()
         ->appendOutputTo(storage_path('logs/server-status.log'));
        // \App\Console\Commands\MonitorSubscriptionStatus::class  // Daftarkan perintah di sini
        // ->everyMinute()

        // Tambahkan jadwal untuk mikrotik:create-secret
        $schedule->command('mikrotik:create-secret')
            ->everyFiveMinutes() // Atau sesuaikan dengan kebutuhan, misalnya ->daily()
            ->appendOutputTo(storage_path('logs/mikrotik-create-secret.log'));

                 
        // Uncomment ini untuk production nanti
        // $schedule->command('invoice:generate-due')
        //         ->dailyAt('17:00');
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