<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class MikrotikServer extends Model
{
    protected $fillable = [
        'name',
        'host_ip',
        'username',
        'password',
        'port',
        'ros_version',
        'is_active',
        'last_connection_status',
        'last_connected_at'
    ];

    protected $dates = [
        'last_connected_at',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'port' => 'integer'
    ];

    // Enkripsi password saat disimpan
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Crypt::encryptString($value);
    }

    // Dekripsi password saat diakses
    public function getPasswordAttribute($value)
    {
        try {
            return $value ? Crypt::decryptString($value) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    // Scope untuk server aktif
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Relasi dengan PPPoE Users jika diperlukan
    // public function pppoeUsers()
    // {
    //     return $this->hasMany(PppoeUser::class, 'server_id');
    // }

    /**
     * Get the metrics for this server
     */
    public function metrics()
    {
        return $this->hasMany(ServerMetric::class);
    }

    /**
     * Get the latest metric for this server
     */
    public function latestMetric()
    {
        return $this->hasOne(ServerMetric::class)->latest();
    }

    


}