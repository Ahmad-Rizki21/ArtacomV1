<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class DataTeknis extends Model
{
    protected $table = 'data_teknis';
   

    protected $fillable = [
        'pelanggan_id',   // 🔥 Foreign key ke tabel pelanggan
        'id_pelanggan',   // 🔥 ID unik dari Data Teknis
        'id_vlan',
        'password_pppoe',
        'ip_pelanggan',
        'profile_pppoe',
        'olt',
        'pon',
        'otb',
        'odc',
        'odp',
        'onu_power',
    ];
    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class);
    }

    // 🔗 Relasi ke Invoice
    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'id_pelanggan', 'id_pelanggan');
    }

}
