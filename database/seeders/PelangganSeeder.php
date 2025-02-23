<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PelangganSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    public function run()
    {
        \App\Models\Pelanggan::create([
            'no_ktp' => '3201012908920013',
            'nama' => 'Dony Sanjaya',
            'alamat' => 'Pulogebang',
            'blok' => 'F',
            'unit' => '205',
            'no_telp' => '62812859606822',
            'email' => 'sanjayadony13@gmail.com',
        ]);

        // \App\Models\Pelanggan::create([
        //     'no_ktp' => '320101290891998',
        //     'nama' => 'Ahmad Rizki',
        //     'alamat' => 'Jakarta',
        //     'blok' => 'F',
        //     'unit' => '205',
        //     'no_telp' => '628987654321',
        //     'email' => 'ahmadrizki1234@gmail.com',
        // ]);
    }
    


}
