<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LanggananSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ambil data langganan yang sudah ada
        $langganans = DB::table('langganan')->get();

        foreach ($langganans as $langganan) {
            // Gunakan tanggal created_at sebagai tanggal berlangganan
            $tanggalBerlangganan = Carbon::parse($langganan->created_at);

            // Hitung tanggal jatuh tempo (bulan berikutnya, pada tanggal yang sama)
            $tanggalJatuhTempo = $tanggalBerlangganan->copy()->addMonth();

            // Update data langganan dengan tanggal jatuh tempo
            DB::table('langganan')
                ->where('id', $langganan->id)
                ->update([
                    'tgl_jatuh_tempo' => $tanggalJatuhTempo
                ]);
        }
    }
}