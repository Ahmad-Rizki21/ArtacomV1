<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Langganan extends Model
{
    use HasFactory;

    protected $table = 'langganan';

    protected $fillable = [
        'pelanggan_id',
        'id_brand',
        'layanan',
        'total_harga_layanan_x_pajak',
    ];

    // Relasi ke pelanggan
    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class, 'pelanggan_id');
    }

    // Relasi ke harga layanan
    public function hargaLayanan()
    {
        return $this->belongsTo(HargaLayanan::class, 'id_brand', 'id_brand');
    }

    protected static function boot()
{
    parent::boot();

    static::creating(function ($langganan) {
        $hargaLayanan = HargaLayanan::find($langganan->id_brand);

        if ($hargaLayanan) {
            $harga = match ($langganan->layanan) {
                '10 Mbps' => $hargaLayanan->harga_10mbps,
                '20 Mbps' => $hargaLayanan->harga_20mbps,
                '30 Mbps' => $hargaLayanan->harga_30mbps,
                '50 Mbps' => $hargaLayanan->harga_50mbps,
                default => 0,
            };

            $pajak = ($hargaLayanan->pajak / 100) * $harga;
            $langganan->total_harga_layanan_x_pajak = $harga + $pajak;
        }
    });
}

    // 🔗 Relasi ke Invoice
    // public function invoices()
    // {
    //     return $this->hasMany(Invoice::class, 'pelanggan_id', 'pelanggan_id');
    // }

}
