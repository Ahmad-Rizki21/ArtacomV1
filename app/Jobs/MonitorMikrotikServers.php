<?php

namespace App\Jobs;

use App\Models\MikrotikServer;
use App\Services\MikrotikConnectionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MonitorMikrotikServers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $server;

    public function __construct(MikrotikServer $server = null)
    {
        $this->server = $server;
    }

    public function handle(): void
    {
        try {
            $service = new MikrotikConnectionService();
            
            if ($this->server) {
                // Monitor specific server
                Log::info('Monitoring Mikrotik server: ' . $this->server->name);
                $service->collectMetrics($this->server);
            } else {
                // Monitor all active servers
                $servers = MikrotikServer::where('is_active', true)->get();
                Log::info('Monitoring ' . $servers->count() . ' Mikrotik servers');
                
                foreach ($servers as $server) {
                    $service->collectMetrics($server);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error in Mikrotik monitoring job: ' . $e->getMessage());
        }
    }
}