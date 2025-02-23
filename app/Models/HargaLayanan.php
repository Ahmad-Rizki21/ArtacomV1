<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HargaLayanan extends Model
{
    use HasFactory;

    protected $table = 'harga_layanan';

    protected $fillable = [
        'id_brand',
        'brand',
        'pajak',
        'harga_10mbps',
        'harga_20mbps',
        'harga_30mbps',
        'harga_50mbps',
    ];

    protected $primaryKey = 'id_brand';
    public $incrementing = false;
    protected $keyType = 'string';

    // Relasi ke langganan
    public function langganan()
    {
        return $this->hasMany(Langganan::class, 'id_brand', 'id_brand');
    }

    

}
