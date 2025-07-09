<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\Telegram;

class TelegramSendStatusCommand extends Command
{
    /**
     * Signature command diubah untuk menerima pesan error opsional.
     */
    protected $signature = 'telegram:send-status {--status=success : Status of the job (success or failure)} {errorMessage?}';

    protected $description = 'Sends a status notification to Telegram with better formatting';

    public function handle()
    {
        $status = $this->option('status');
        $appName = config('app.name', 'Billing System'); // Ambil nama aplikasi dari .env
        $timestamp = now()->format('d M Y, H:i:s T'); // Format waktu yang jelas

        if ($status === 'success') {
            $tasks = [
                "- Mikrotik Server Log Succes",
                "- Invoice Server Log Succes",
                "- Suspended Server Log Succes",
            ];

            $message = sprintf(
                "✅ *Cronjob Sukses*\n\n" .
                "Aplikasi *%s* telah berhasil menjalankan tugas terjadwal.\n\n" .
                "*Detail Tugas:*\n```\n%s\n```\n" .
                "*Waktu Eksekusi:*\n%s",
                $appName,
                implode("\n", $tasks),
                $timestamp
            );

            Telegram::sendMessage($message);
            $this->info('Success notification sent to Telegram.');

        } elseif ($status === 'failure') {
            $errorMessage = $this->argument('errorMessage') ?? 'Tidak ada pesan error spesifik.';

            $message = sprintf(
                "🚨 *CRONJOB GAGAL*\n\n" .
                "Terjadi error pada aplikasi *%s* saat menjalankan tugas terjadwal.\n\n" .
                "*Pesan Error:*\n```\n%s\n```\n" .
                "*Waktu Eksekusi:*\n%s\n\n" .
                "Mohon segera periksa log server untuk investigasi lebih lanjut.",
                $appName,
                $errorMessage,
                $timestamp
            );

            Telegram::sendMessage($message);
            $this->info('Failure notification sent to Telegram.');
        }

        return Command::SUCCESS;
    }
}