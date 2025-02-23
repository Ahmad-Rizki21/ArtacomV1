<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LanggananSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
{
    $pelanggan = \App\Models\Pelanggan::first(); // Ambil pelanggan pertama
    $hargaLayanan = \App\Models\HargaLayanan::first(); // Ambil harga layanan pertama

    $pelanggan->langganan()->create([
        'id_brand' => $hargaLayanan->id,
        'layanan' => '10 Mbps',
        'total_harga_layanan_x_pajak' => 165000,
    ]);
}

}
