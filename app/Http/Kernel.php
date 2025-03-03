<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * @var array
     */
    protected $middleware = [
        // Keamanan dan performa
        \Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Http\Middleware\HandleCors::class,
        \Illuminate\Http\Middleware\TrustProxies::class,
        
        // Middleware dasar
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
        
        // Middleware khusus untuk logging dan keamanan
        // \App\Http\Middleware\TrimStrings::class,
        // \App\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            // Middleware untuk request web
            // \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            
            // Middleware keamanan tambahan
            // \App\Http\Middleware\VerifyXenditWebhook::class, // Middleware validasi webhook Xendit
        ],

        'api' => [
            // Middleware untuk request API
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            
            // Middleware keamanan API
            // \App\Http\Middleware\ApiRequestValidator::class, // Middleware validasi request API
            // \App\Http\Middleware\CheckApiAuthentication::class, // Middleware autentikasi API
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware = [
        // 'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        // 'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        'verifyXenditWebhook' => \App\Http\Middleware\VerifyXenditWebhook::class,

        // Middleware khusus untuk Xendit
        // 'xendit.webhook' => \App\Http\Middleware\VerifyXenditWebhook::class,
    ];

    
}