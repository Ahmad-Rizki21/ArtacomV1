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
        ->everyMinutes()
        ->appendOutputTo(storage_path('logs/invoice-scheduler.log'));

        $schedule->command('app:check-overdue-subscriptions')
        ->everyMinutes()       
        //  ->daily()
        //  ->at('00:01')
                 ->appendOutputTo(storage_path('logs/subscription-checks.log'));

        $schedule->command('check:due-date')
        ->everyMinutes()
        ->appendOutputTo(storage_path('logs/due-date-check.log'));

        $schedule->command('app:sync-mikrotik-status')
        ->everyMinutes()
        ->appendOutputTo(storage_path('logs/mikrotik-sync.log'));

        $schedule->call(function () {
            Langganan::checkAllSubscriptionStatus();
        })->everyMinutes();

        $schedule->command('invoice:check-paid-status')->everyMinute();


        // \App\Console\Commands\MonitorSubscriptionStatus::class  // Daftarkan perintah di sini
        // ->everyMinute()

                 
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