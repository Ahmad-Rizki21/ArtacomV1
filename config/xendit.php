<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Xendit API Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi untuk integrasi dengan Xendit Payment Gateway
    |
    */

    // API Key dari dashboard Xendit
    'api_key' => env('XENDIT_API_KEY', ''),
    
    // API Secret dari dashboard Xendit (jika diperlukan)
    'api_secret' => env('XENDIT_API_SECRET', ''),
    
    // Token untuk verifikasi webhook
    // 'webhook_token' => env('XENDIT_WEBHOOK_TOKEN', ''),
    
    // Apakah menggunakan mode sandbox (staging)
    // 'is_sandbox' => env('XENDIT_IS_SANDBOX', true),
    
    // Base URL untuk API Xendit
    'base_url' => env('XENDIT_BASE_URL', 'https://api.xendit.co'),
];