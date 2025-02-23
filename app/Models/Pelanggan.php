<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pelanggan extends Model
{
    use HasFactory;

    protected $table = 'pelanggan';

    protected $fillable = [
        'no_ktp',        // Add this line
        'nama',
        'alamat',
        'blok',
        'unit',
        'no_telp',
        'email',
        // any other fields you want to mass assign
    ];
    
    public function dataTeknis()
    {
        return $this->hasOne(DataTeknis::class);
    }


    public function langganan()
    {
        return $this->hasMany(Langganan::class);
    }

    // // 🔗 Relasi ke Invoice
    // public function invoices()
    // {
    //     return $this->hasMany(Invoice::class, 'pelanggan_id');
    // }
}

