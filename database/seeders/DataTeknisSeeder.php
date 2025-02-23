<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DataTeknis;

class DataTeknisSeeder extends Seeder
{
    public function run()
    {
        $pelanggan = \App\Models\Pelanggan::first(); // Ambil pelanggan pertama
    
        $pelanggan->dataTeknis()->create([
            'id_vlan' => '103',
            'id_pelanggan' => 'pgb-sanjaya-10',
            'password_pppoe' => 'sanjaya1845',
            'ip_pelanggan' => '103.103.100.17',
            'profile_pppoe' => 'PGB-10M-A',
            'olt' => 'Pulogebang',
            'pon' => 1,
            'otb' => 2,
            'odc' => 2,
            'odp' => 1,
            'onu_power' => -20,
        ]);
    }
    
}

