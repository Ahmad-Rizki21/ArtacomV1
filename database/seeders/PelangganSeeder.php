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
            'no_ktp' => '3525016060565004',
            'nama' => 'Joni',
            'alamat' => 'Rusun Pulogebang Tower',
            'blok' => 'TWR',
            'unit' => '12',
            'no_telp' => '08986937819',
            'email' => 'ahmad2005rizki@gmail.com',
        ]);

        \App\Models\Pelanggan::create([
            'no_ktp' => '3525016060565090',
            'nama' => 'Mamad',
            'alamat' => 'Rusun Pinus Elok',
            'blok' => 'A3',
            'unit' => '12',
            'no_telp' => '08986937819',
            'email' => 'ahmad2005rizki@gmail.com',
        ]);

        \App\Models\Pelanggan::create([
            'no_ktp' => '3525016060650032',
            'nama' => 'Kipli',
            'alamat' => 'Rusun KM2',
            'blok' => 'B',
            'unit' => '13',
            'no_telp' => '08986937819',
            'email' => 'ahmad2005rizki@gmail.com',
        ]);

        \App\Models\Pelanggan::create([
            'no_ktp' => '3525016060650044',
            'nama' => 'Jacob',
            'alamat' => 'Rusun Nagrak',
            'blok' => 'TWR 2',
            'unit' => '13',
            'no_telp' => '08986937819',
            'email' => 'ahmad2005rizki@gmail.com',
        ]);

        \App\Models\Pelanggan::create([
            'no_ktp' => '3525016060650021',
            'nama' => 'Martin',
            'alamat' => 'Rusun Tipar Cakung',
            'blok' => 'Angsana',
            'unit' => '1',
            'no_telp' => '08986937819',
            'email' => 'ahmad2005rizki@gmail.com',
        ]);

        \App\Models\Pelanggan::create([
            'no_ktp' => '3525016060650009',
            'nama' => 'Rahmat',
            'alamat' => 'Rusun Albo',
            'blok' => 'C',
            'unit' => '23',
            'no_telp' => '08986937819',
            'email' => 'ahmad2005rizki@gmail.com',
        ]);

        \App\Models\Pelanggan::create([
            'no_ktp' => '3525016060650887',
            'nama' => 'Mansur',
            'alamat' => 'Perumahan Waringin',
            'blok' => 'A',
            'unit' => '14',
            'no_telp' => '08986937819',
            'email' => 'ahmad2005rizki@gmail.com',
        ]);

        \App\Models\Pelanggan::create([
            'no_ktp' => '3525016060650071',
            'nama' => 'Roli',
            'alamat' => 'Perumahan Parama',
            'blok' => 'B',
            'unit' => '14',
            'no_telp' => '08986937819',
            'email' => 'ahmad2005rizki@gmail.com',
        ]);

        \App\Models\Pelanggan::create([
            'no_ktp' => '3525016060650333',
            'nama' => 'Jack',
            'alamat' => 'Jl. Komarudin Baru',
            'blok' => '23',
            'unit' => '32',
            'no_telp' => '08986937819',
            'email' => 'ahmad2005rizki@gmail.com',
        ]);

        \App\Models\Pelanggan::create([
            'no_ktp' => '3525016060650123',
            'nama' => 'Jarjit',
            'alamat' => 'Jl. Kompas Tambun',
            'blok' => '22',
            'unit' => '132',
            'no_telp' => '08986937819',
            'email' => 'ahmad2005rizki@gmail.com',
        ]);
    }
}