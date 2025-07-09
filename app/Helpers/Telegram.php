<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Telegram
{
    public static function sendMessage(string $message)
    {
        // Ambil token dan chat ID dari file config
        // (Kita akan atur ini di langkah berikutnya)
        $token = config('services.telegram.bot_token');
        $chatId = config('services.telegram.chat_id');

        // Jika token atau chat ID tidak ada, jangan lakukan apa-apa
        // dan catat sebagai error di log server.
        if (!$token || !$chatId) {
            Log::error('Telegram Bot Token or Chat ID is not configured.');
            return;
        }

        // URL API Telegram untuk mengirim pesan
        $url = "https://api.telegram.org/bot{$token}/sendMessage";

        try {
            // Kirim request ke API Telegram menggunakan HTTP Client Laravel
            Http::post($url, [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'Markdown' // Agar bisa pakai format *tebal* atau `monospace`
            ]);
        } catch (\Exception $e) {
            // Jika pengiriman gagal, catat error di log server
            // agar tidak menghentikan proses utama aplikasi Anda.
            Log::error('Failed to send Telegram message: ' . $e->getMessage());
        }
    }
}