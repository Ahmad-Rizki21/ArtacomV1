<?php

namespace App\Providers;

use Dflydev\DotAccessData\Data;
use Illuminate\Container\Attributes\Database;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Facades\Health;
use Spatie\Health\Checks\Checks\OptimizedAppCheck;
use Spatie\Health\Checks\Checks\DebugModeCheck;
use Spatie\Health\Checks\Checks\EnvironmentCheck;
use App\Observers\DataTeknisObserver;
use App\Observers\InvoiceObserver;
use App\Services\MikrotikConnectionService;
use App\Models\DataTeknis;
use App\Models\Invoice;
use Spatie\CpuLoadHealthCheck\CpuLoadCheck;
use Spatie\MemoryUsageHealthCheck\MemoryUsageCheck;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(MikrotikConnectionService::class, function ($app) {
        return new MikrotikConnectionService();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        DataTeknis::observe(DataTeknisObserver::class);
        Invoice::observe(InvoiceObserver::class);
        
        Schema::defaultStringLength(191);
        Health::checks([
            OptimizedAppCheck::new(),
            DebugModeCheck::new(),
            EnvironmentCheck::new(),
            DatabaseCheck::new(),
            // CpuLoadCheck::new()
            // ->failWhenLoadIsHigherInTheLast5Minutes(2.0)
            // ->failWhenLoadIsHigherInTheLast15Minutes(1.5),
            
        ]);
        
    }
}