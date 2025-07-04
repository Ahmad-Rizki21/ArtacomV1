<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Events\LanggananCreatedWithoutDataTeknis;
use App\Events\InvoiceCreated;
use App\Listeners\SendInvoiceToXendit;
use App\Listeners\SendNocNotification;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
            LanggananCreatedWithoutDataTeknis::class => [
                SendNocNotification::class,

            ],
                \App\Events\PelangganCreated::class => [
                \App\Listeners\SendNocNotificationToCreateDataTeknis::class,
            ],

            

        ],
        
        // Perbaiki referensi listener disini
        // InvoiceCreated::class => [
        //     SendInvoiceToXendit::class, // Pastikan ini sesuai dengan nama class yang Anda miliki
        // ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }
}