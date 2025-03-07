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
        'olt_custom', // Add this line
        'pon',
        'otb',
        'odc',
        'odp',
        'onu_power',
    ];


    // Boot method untuk set default value jika kolom kosong
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($dataTeknis) {
            // Cek dan set kolom yang tidak diisi dengan "No Data"
            // $dataTeknis->id_vlan = $dataTeknis->id_vlan ?: 'No Data';
            $dataTeknis->pon = $dataTeknis->pon ?? 0; // Numeric column
            $dataTeknis->otb = $dataTeknis->otb ?? 0; // Numeric column
            $dataTeknis->odc = $dataTeknis->odc ?? 0; // Numeric column
            $dataTeknis->odp = $dataTeknis->odp ?? 0; // Numeric column
            $dataTeknis->onu_power = $dataTeknis->onu_power ?? 0; // Numeric column

        });
    }


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
