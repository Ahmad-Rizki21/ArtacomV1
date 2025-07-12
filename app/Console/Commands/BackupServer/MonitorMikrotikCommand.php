<?php

namespace App\Console\Commands;

use App\Jobs\MonitorMikrotikServers;
use App\Models\MikrotikServer;
use Illuminate\Console\Command;

class MonitorMikrotikCommand extends Command
{
    protected $signature = 'monitor:mikrotik {server_id?}';
    protected $description = 'Monitor Mikrotik servers and collect metrics';

    public function handle()
    {
        if ($serverId = $this->argument('server_id')) {
            $server = MikrotikServer::find($serverId);
            
            if (!$server) {
                $this->error("Server with ID {$serverId} not found");
                return 1;
            }
            
            $this->info("Monitoring server: {$server->name}");
            MonitorMikrotikServers::dispatch($server);
        } else {
            $servers = MikrotikServer::where('is_active', true)->get();
            $this->info("Monitoring {$servers->count()} active servers");
            MonitorMikrotikServers::dispatch();
        }
        
        $this->info('Monitoring job dispatched successfully');
        return 0;
    }
}