<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pelanggan;
use App\Models\DataTeknis;

class DataTeknisSeeder extends Seeder
{
    public function run()
    {
        // Joni
        $joni = Pelanggan::where('nama', 'Joni')->first();
        DataTeknis::create([
            'pelanggan_id' => $joni->id,
            'id_vlan' => 103,
            'id_pelanggan' => 'PGB-TWR-10-Joni',
            'password_pppoe' => 'Joni2024',
            'ip_pelanggan' => '101.101.22.22',
            'profile_pppoe' => 'Joni-10M',
            'olt' => 'Pulogebang Tower',
            'pon' => 0,
            'otb' => 0,
            'odc' => 0,
            'odp' => 0,
            'onu_power' => 0,
        ]);

        // Mamad
        $mamad = Pelanggan::where('nama', 'Mamad')->first();
        DataTeknis::create([
            'pelanggan_id' => $mamad->id,
            'id_vlan' => 105,
            'id_pelanggan' => 'PIN-A3-10-Mamad',
            'password_pppoe' => 'Mamad2024',
            'ip_pelanggan' => '101.101.22.23',
            'profile_pppoe' => 'Mamad-10M',
            'olt' => 'Pinus Elok',
            'pon' => 0,
            'otb' => 0,
            'odc' => 0,
            'odp' => 0,
            'onu_power' => 0,
        ]);

        // Kipli
        $kipli = Pelanggan::where('nama', 'Kipli')->first();
        DataTeknis::create([
            'pelanggan_id' => $kipli->id,
            'id_vlan' => 106,
            'id_pelanggan' => 'KM2-B-10-Mamad',
            'password_pppoe' => 'Kipli2025',
            'ip_pelanggan' => '101.101.22.24',
            'profile_pppoe' => 'Kipli-10M',
            'olt' => 'Pinus Elok',
            'pon' => 0,
            'otb' => 0,
            'odc' => 0,
            'odp' => 0,
            'onu_power' => 0,
        ]);

        // Jacob
        $jacob = Pelanggan::where('nama', 'Jacob')->first();
        DataTeknis::create([
            'pelanggan_id' => $jacob->id,
            'id_vlan' => 107,
            'id_pelanggan' => 'NGR-TWR2-10-Jacob',
            'password_pppoe' => 'Jacob2026',
            'ip_pelanggan' => '101.101.22.25',
            'profile_pppoe' => 'Jacob-10M',
            'olt' => 'Nagrak',
            'pon' => 0,
            'otb' => 0,
            'odc' => 0,
            'odp' => 0,
            'onu_power' => 0,
        ]);

        // Martin
        $martin = Pelanggan::where('nama', 'Martin')->first();
        DataTeknis::create([
            'pelanggan_id' => $martin->id,
            'id_vlan' => 108,
            'id_pelanggan' => 'CKG-ANGSA-10-Martin',
            'password_pppoe' => 'Martin2027',
            'ip_pelanggan' => '101.101.22.26',
            'profile_pppoe' => 'Martin-10M',
            'olt' => 'Tipar Cakung',
            'pon' => 0,
            'otb' => 0,
            'odc' => 0,
            'odp' => 0,
            'onu_power' => 0,
        ]);

        // Rahmat
        $rahmat = Pelanggan::where('nama', 'Rahmat')->first();
        DataTeknis::create([
            'pelanggan_id' => $rahmat->id,
            'id_vlan' => 109,
            'id_pelanggan' => 'CKG-BRI-C-10-Rahmat',
            'password_pppoe' => 'Rahmat2028',
            'ip_pelanggan' => '101.101.22.27',
            'profile_pppoe' => 'Rahmat-10M',
            'olt' => 'Albo',
            'pon' => 0,
            'otb' => 0,
            'odc' => 0,
            'odp' => 0,
            'onu_power' => 0,
        ]);

        // Mansur
        $mansur = Pelanggan::where('nama', 'Mansur')->first();
        DataTeknis::create([
            'pelanggan_id' => $mansur->id,
            'id_vlan' => 100,
            'id_pelanggan' => 'WRN-A-14-Mansur',
            'password_pppoe' => 'Mansur2024',
            'ip_pelanggan' => '192.168.20.20',
            'profile_pppoe' => 'Mansur-10M',
            'olt' => 'Lainnya > Waringin',
            'pon' => 0,
            'otb' => 0,
            'odc' => 0,
            'odp' => 0,
            'onu_power' => 0,
        ]);

        // Roli
        $roli = Pelanggan::where('nama', 'Roli')->first();
        DataTeknis::create([
            'pelanggan_id' => $roli->id,
            'id_vlan' => 100,
            'id_pelanggan' => 'PRM-B-14-Roli',
            'password_pppoe' => 'Roli2025',
            'ip_pelanggan' => '192.168.20.21',
            'profile_pppoe' => 'Roli-10M',
            'olt' => 'Lainnya > Parama',
            'pon' => 0,
            'otb' => 0,
            'odc' => 0,
            'odp' => 0,
            'onu_power' => 0,
        ]);

        // Jack
        $jack = Pelanggan::where('nama', 'Jack')->first();
        DataTeknis::create([
            'pelanggan_id' => $jack->id,
            'id_vlan' => 200,
            'id_pelanggan' => 'KMD-23-32-Jack',
            'password_pppoe' => 'Jack2024',
            'ip_pelanggan' => '192.168.30.10',
            'profile_pppoe' => 'Jack-10M',
            'olt' => 'Pinus Elok',
            'pon' => 0,
            'otb' => 0,
            'odc' => 0,
            'odp' => 0,
            'onu_power' => 0,
        ]);

        // Jarjit
        $jarjit = Pelanggan::where('nama', 'Jarjit')->first();
        DataTeknis::create([
            'pelanggan_id' => $jarjit->id,
            'id_vlan' => 300,
            'id_pelanggan' => 'TMB-22-132-Jarjit',
            'password_pppoe' => 'Jarjit2024',
            'ip_pelanggan' => '192.168.80.12',
            'profile_pppoe' => 'Jarjit-10M',
            'olt' => 'Tambun',
            'pon' => 0,
            'otb' => 0,
            'odc' => 0,
            'odp' => 0,
            'onu_power' => 0,
        ]);
    }
}