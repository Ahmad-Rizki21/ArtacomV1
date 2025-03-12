<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\HargaLayanan;

class HargaLayananSeeder extends Seeder
{
    public function run()
    {
        // Data for Jakinet
        \App\Models\HargaLayanan::create([
            'id_brand' => 'ajn-01',
            'brand' => 'Jakinet',
            'pajak' => 11,
            'harga_10mbps' => 135135,
            'harga_20mbps' => 199000,
            'harga_30mbps' => 224000,
            'harga_50mbps' => 254000,
        ]);

        // Data for Jelantik
        \App\Models\HargaLayanan::create([
            'id_brand' => 'ajn-02',
            'brand' => 'Jelantik',
            'pajak' => 11,
            'harga_10mbps' => 150000,
            'harga_20mbps' => 209000,
            'harga_30mbps' => 249000,
            'harga_50mbps' => 289900,
        ]);

        // Data for Jelantik Nagrak
        \App\Models\HargaLayanan::create([
            'id_brand' => 'ajn-03',
            'brand' => 'Jelantik Nagrak',
            'pajak' => 11,
            'harga_10mbps' => 135135,
            'harga_20mbps' => 199000,
            'harga_30mbps' => 224000,
            'harga_50mbps' => 254000,
        ]);

    }
}




