<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Request;

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
        'tgl_invoice_terakhir',
        'metode_pembayaran',
        'user_status'  // Menambahkan kolom user_status
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

//     protected static function boot()
// {
//     parent::boot();

//     static::creating(function ($langganan) {
//         // Cek apakah metode pembayaran manual
//         $isManual = request()->input('metode_pembayaran') === 'manual';
        
//         if ($isManual) {
//             // Gunakan harga manual yang diinput
//             $manualHarga = request()->input('total_harga_layanan_x_pajak');
//             $langganan->hitungTotalHarga(true, $manualHarga);
//         } else {
//             // Hitung otomatis
//             $langganan->hitungTotalHarga();
//         }

//         if (is_null($langganan->tgl_jatuh_tempo)) {
//             $langganan->setTanggalJatuhTempo();
//         }
//     });

//     static::updating(function ($langganan) {
//         // Cek apakah metode pembayaran manual
//         $isManual = request()->input('metode_pembayaran') === 'manual';
        
//         if ($isManual) {
//             // Gunakan harga manual yang diinput
//             $manualHarga = request()->input('total_harga_layanan_x_pajak');
//             $langganan->hitungTotalHarga(true, $manualHarga);
//         } else {
//             // Hitung ulang jika layanan atau brand berubah
//             if ($langganan->isDirty(['layanan', 'id_brand'])) {
//                 $langganan->hitungTotalHarga();
//                 $langganan->setTanggalJatuhTempo();
//             }
//         }
//     });
// }

   


protected static function boot()
{
    parent::boot();

    static::creating(function ($langganan) {
        // Cek metode pembayaran
        $metodePembayaran = request()->input('metode_pembayaran');
        
        if ($metodePembayaran === 'manual') {
            // Gunakan harga manual
            $manualHarga = request()->input('total_harga_layanan_x_pajak');
            if ($manualHarga) {
                $langganan->total_harga_layanan_x_pajak = $manualHarga;
            }
        } else {
            // Hitung otomatis - pastikan hitung selalu dilakukan saat otomatis
            $langganan->hitungTotalHarga();
            
            // Log nilai setelah perhitungan
            Log::info('Total Harga Setelah Perhitungan di Model', [
                'total_harga' => $langganan->total_harga_layanan_x_pajak
            ]);
        }

        if (is_null($langganan->tgl_jatuh_tempo)) {
            $langganan->setTanggalJatuhTempo();
        }
    });
}





public function hitungTotalHarga($isManual = false, $manualHarga = null)
    {
        // Jika manual dan ada harga manual, gunakan harga manual
        if ($isManual && $manualHarga !== null) {
            $this->total_harga_layanan_x_pajak = $manualHarga;
            return $manualHarga;
        }
    
        // Jika otomatis, hitung berdasarkan harga layanan
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

    public function setTanggalJatuhTempo($tanggalBerlangganan = null)
    {
        // Gunakan tanggal berlangganan yang diberikan, atau gunakan tanggal saat ini
        $tanggal = $tanggalBerlangganan ? Carbon::parse($tanggalBerlangganan) : Carbon::now();
        
        // Jika tanggal jatuh tempo belum diatur oleh admin, set default tanggal 1 bulan depan
        if (!$this->tgl_jatuh_tempo) {
            $this->tgl_jatuh_tempo = $tanggal->copy()->addMonth()->startOfMonth();
        }

        return $this;
    }

    public function getUserStatusAttribute()
    {
        $latestInvoice = $this->invoices()->latest('created_at')->first();

        if (!$latestInvoice) {
            return 'Tidak Ada Invoice';
        }

        if (in_array($latestInvoice->status_invoice, ['Selesai', 'Lunas', 'Kadaluarsa'])) {
            return 'Aktif';
        } else {
            return 'Suspend';
        }  
    }
    
}