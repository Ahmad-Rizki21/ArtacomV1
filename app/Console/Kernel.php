<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Untuk development - jalankan setiap menit
        $schedule->command('invoice:generate-due --days=5')
        ->everyMinute()
        ->appendOutputTo(storage_path('logs/invoice-scheduler.log'));
                 
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