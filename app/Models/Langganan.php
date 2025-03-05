<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\HargaLayanan;

class Langganan extends Model
{
    use HasFactory;

    protected $table = 'langganan';

    protected $fillable = [
        'pelanggan_id',
        'id_brand',
        'layanan',
        'total_harga_layanan_x_pajak',
        'tgl_jatuh_tempo',
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

    // Relasi ke invoice
    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'pelanggan_id', 'pelanggan_id');
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

    // Method untuk mengatur tanggal jatuh tempo
    public function setTanggalJatuhTempo($tanggalBerlangganan = null)
    {
        $tanggal = $tanggalBerlangganan ? Carbon::parse($tanggalBerlangganan) : Carbon::now();
        $this->tgl_jatuh_tempo = $tanggal->copy()->addMonth()->startOfMonth();
        
        return $this;
    }

    // Menambahkan method untuk menghitung tanggal jatuh tempo
    public function updateTanggalJatuhTempo()
    {
        $this->setTanggalJatuhTempo();
        $this->save();
    }

    // Method untuk update manual jika diperlukan
    public function updateTotalHarga()
    {
        $this->hitungTotalHarga();
        $this->save();
    }

    // Get status user berdasarkan invoice terbaru
    public function getUserStatusAttribute()
    {
        $latestInvoice = $this->invoices()->latest('created_at')->first();

        if (!$latestInvoice) {
            return 'Tidak Ada Invoice';
        }

        if (in_array($latestInvoice->status_invoice, ['Selesai', 'Lunas'])) {
            return 'Aktif';
        } else {
            return 'Suspend';
        }
    }

    // Boot method untuk penanganan model event
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($langganan) {
            $langganan->hitungTotalHarga();
            if (is_null($langganan->tgl_jatuh_tempo)) {
                $langganan->setTanggalJatuhTempo(); // Menetapkan default tanggal jatuh tempo
            }
        });

        static::updating(function ($langganan) {
            if ($langganan->isDirty(['layanan', 'id_brand'])) {
                $langganan->hitungTotalHarga();
                $langganan->setTanggalJatuhTempo();
            }
        });
    }
}
