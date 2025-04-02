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
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
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