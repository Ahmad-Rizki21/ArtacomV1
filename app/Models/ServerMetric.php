<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServerMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'mikrotik_server_id',
        'cpu_load',
        'memory_usage', 
        'disk_usage',
        'uptime',
        'interfaces_traffic',
        'active_connections',
        'system_resources',
        'additional_info' // Hapus duplikat
    ];

    protected $casts = [
        'cpu_load' => 'float',
        'memory_usage' => 'float',
        'disk_usage' => 'float',
        'interfaces_traffic' => 'array',
        'active_connections' => 'array',
        'system_resources' => 'array',
        'additional_info' => 'array',
    ];

    /**
     * Get the server that owns the metric
     */
    public function server()
    {
        return $this->belongsTo(MikrotikServer::class, 'mikrotik_server_id');
    }
}