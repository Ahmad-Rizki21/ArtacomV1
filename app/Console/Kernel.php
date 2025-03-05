<?php

namespace App\Console;

use App\Jobs\CheckInvoiceStatus;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    // protected function schedule(Schedule $schedule)
    // {
    //     // Menjadwalkan pengecekan status invoice setiap 5 menit
    //     $schedule->job(new CheckInvoiceStatus())->everyFiveMinutes();

    // }

    protected function schedule(Schedule $schedule)
    {
        // Jalankan setiap jam 12 malam di production
        // $schedule->command('invoices:update-expired')->dailyAt('00:00');

        // Untuk testing, jalankan setiap 1 menit
        $schedule->command('invoices:update-expired')->everyMinute();
        $schedule->command('invoices:generate-invoices')->everyMinute();
        // $schedule->command('invoices:generate')->dailyAt('00:00');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');

    }
}
