<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $table = 'invoices';

    protected $fillable = [
        'invoice_number',
        'pelanggan_id',
        'id_pelanggan',
        'brand',
        'total_harga',
        'no_telp',
        'email',
        'tgl_invoice',
        'tgl_jatuh_tempo',
        'payment_link',  // Simpan link dari Xendit
        'status_invoice', // Status Pembayaran
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($invoice) {
            // Generate Nomor Invoice secara otomatis
            $invoice->invoice_number = 'INV-' . now()->format('Ymd') . '-' . rand(1000, 9999);
    
            // 🔹 Ambil `id_pelanggan` dari `data_teknis` berdasarkan `pelanggan_id`
            $dataTeknis = \App\Models\DataTeknis::where('pelanggan_id', $invoice->pelanggan_id)->first();
            if ($dataTeknis) {
                $invoice->id_pelanggan = $dataTeknis->id_pelanggan;
            } else {
                throw new \Exception('ID Pelanggan tidak ditemukan di Data Teknis.');
            }
    
            // 🔹 Ambil `brand` dari `langganan`
            $langganan = \App\Models\Langganan::where('pelanggan_id', $invoice->pelanggan_id)->first();
            if ($langganan) {
                $invoice->brand = $langganan->id_brand;
                $invoice->total_harga = $langganan->total_harga_layanan_x_pajak;
            } else {
                throw new \Exception('Brand tidak ditemukan untuk pelanggan ini.');
            }
    
            // 🔹 Ambil `no_telp` dan `email` dari `pelanggan`
            $pelanggan = \App\Models\Pelanggan::find($invoice->pelanggan_id);
            if ($pelanggan) {
                $invoice->no_telp = $pelanggan->no_telp;
                $invoice->email = $pelanggan->email;
            } else {
                throw new \Exception('Nomor Telepon atau Email tidak ditemukan untuk pelanggan ini.');
            }
        });
    }
    
    

    // 🔹 Relasi ke Pelanggan
    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class, 'pelanggan_id', 'id');
    }

    // 🔹 Relasi ke Data Teknis (ID Pelanggan dari ISP)
    public function dataTeknis()
    {
        return $this->belongsTo(DataTeknis::class, 'id_pelanggan', 'id_pelanggan');
    }

    // 🔹 Relasi ke Langganan
    public function langganan()
    {
        return $this->hasOne(Langganan::class, 'pelanggan_id', 'pelanggan_id');
    }

    // 🔹 Relasi ke Harga Layanan
    public function hargaLayanan()
    {
        return $this->hasOne(HargaLayanan::class, 'id_brand', 'brand');
    }
}
