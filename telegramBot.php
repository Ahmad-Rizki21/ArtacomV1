<?php

$logFile = '/home/billing/billing-cronjob.log';
$token = '7405142114:AAFLp8VqpauDVLoTXzcusOyjgZ2TigoJjG0';
$chatId = '1127703225';

// Baca 100 baris terakhir dari log
$lines = shell_exec("tail -n 100 " . escapeshellarg($logFile));

if (stripos($lines, 'error') !== false) {
    $message = "⚠️ Ada error di log cronjob invoice-scheduler:\n";
    // Anda bisa kirimkan beberapa baris error saja agar tidak terlalu panjang
    preg_match_all('/.*error.*$/im', $lines, $matches);
    $errors = implode("\n", array_slice($matches[0], 0, 10)); // maksimal 10 baris error
    $message .= $errors;

    // Kirim pesan ke Telegram
    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    $postFields = [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'HTML',
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
}
