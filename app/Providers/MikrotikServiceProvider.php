<?php

namespace App\Providers;

use App\Services\MikrotikPppoeSecretService;
use Illuminate\Support\ServiceProvider;
use App\Services\MikrotikConnectionService;
use App\Services\MikrotikSubscriptionManager;

class MikrotikServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(MikrotikConnectionService::class, function ($app) {
            return new MikrotikConnectionService();
        });

        $this->app->singleton(MikrotikSubscriptionManager::class, function ($app) {
            return new MikrotikSubscriptionManager(
                $app->make(MikrotikConnectionService::class)
            );
        });

        $this->app->singleton(MikrotikPppoeSecretService::class, function ($app) {
            return new MikrotikPppoeSecretService(
                $app->make(MikrotikConnectionService::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}