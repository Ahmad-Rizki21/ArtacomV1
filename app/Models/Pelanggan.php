<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Log;

class Pelanggan extends Model
{
    use HasFactory;

    protected $table = 'pelanggan';

    protected $fillable = [
        'no_ktp',
        'nama',
        'alamat',
        'blok',
        'unit',
        'no_telp',
        'email',
        'alamat_2',
        'alamat_custom',
        'id_brand',        // Field untuk brand
        'layanan',         // Field untuk paket layanan
        'brand_default',   // Field untuk menyimpan brand default
        'tgl_instalasi',   // Field baru untuk tanggal instalasi
    ];
    
    protected $dates = [
        'tgl_instalasi',  // Menetapkan field tanggal instalasi sebagai tipe date
        'created_at',
        'updated_at',
    ];
    
    // Mutator untuk memastikan nomor telepon selalu memiliki leading zero
    public function setNoTelpAttribute($value)
    {
        // Pastikan selalu diawali 0
        $this->attributes['no_telp'] = substr($value, 0, 1) === '0' 
            ? $value 
            : '0' . $value;
    }

    // Accessor untuk format tampilan (opsional)
    public function getNoTelpAttribute($value)
    {
        // Contoh formatting, bisa disesuaikan
        return strlen($value) > 1 ? $value : null;
    }

    // Relasi ke HargaLayanan (baru)
    public function hargaLayanan()
    {
        return $this->belongsTo(HargaLayanan::class, 'id_brand', 'id_brand');
    }

    // Relasi lainnya tetap sama
    public function dataTeknis()
    {
        return $this->hasOne(DataTeknis::class);
    }

    public function langganan()
    {
        return $this->hasMany(Langganan::class);
    }

    public function setAlamat2Attribute($value)
    {
        Log::info('Nilai alamat_2:', ['value' => $value]);
        $this->attributes['alamat_2'] = $value;
    }
}