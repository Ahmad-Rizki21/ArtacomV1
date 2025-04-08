<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServerStatus extends Model
{
    protected $fillable = [
        'cpu_usage',
        'memory_usage',
        'load_1m',
        'load_5m',
        'load_15m',
        'uptime',
        'process_count',
        'running_processes',
        'raw_data',
        'snapshot_time'
    ];

    protected $casts = [
        'cpu_usage' => 'float',
        'memory_usage' => 'float',
        'load_1m' => 'float',
        'load_5m' => 'float',
        'load_15m' => 'float',
        'process_count' => 'integer',
        'running_processes' => 'integer',
        'raw_data' => 'array',
        'snapshot_time' => 'datetime',
    ];
}