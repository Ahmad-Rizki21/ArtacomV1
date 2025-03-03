<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

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

    // Method untuk menghitung total harga dengan pajak
    public function hitungTotalHarga()
    {
        $hargaLayanan = HargaLayanan::where('id_brand', $this->id_brand)->first();

        if ($hargaLayanan) {
            $harga = match ($this->layanan) {
                '10 Mbps' => $hargaLayanan->harga_10mbps,
                '20 Mbps' => $hargaLayanan->harga_20mbps,
                '30 Mbps' => $hargaLayanan->harga_30mbps,
                '50 Mbps' => $hargaLayanan->harga_50mbps,
                default => 0,
            };

            $pajak = ($hargaLayanan->pajak / 100) * $harga;
            $this->total_harga_layanan_x_pajak = $harga + $pajak;

            // Log untuk debugging
            Log::info('Hitung Total Harga Langganan', [
                'pelanggan_id' => $this->pelanggan_id,
                'layanan' => $this->layanan,
                'harga_dasar' => $harga,
                'pajak' => $pajak,
                'total_harga' => $this->total_harga_layanan_x_pajak
            ]);

            return $this->total_harga_layanan_x_pajak;
        }

        return 0;
    }

    protected static function boot()
    {
        parent::boot();

        // Saat membuat langganan baru
        static::creating(function ($langganan) {
            $langganan->hitungTotalHarga();
        });

        // Saat update langganan
        static::updating(function ($langganan) {
            // Jika layanan atau brand berubah, hitung ulang total harga
            if ($langganan->isDirty(['layanan', 'id_brand'])) {
                $langganan->hitungTotalHarga();
            }
        });
    }

    // Method untuk update manual jika diperlukan
    public function updateTotalHarga()
    {
        $this->hitungTotalHarga();
        $this->save();
    }
}