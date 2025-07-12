<?php

namespace App\Observers;

use App\Models\Langganan;
use App\Models\DataTeknis;
use App\Models\HargaLayanan;
use App\Models\Pelanggan;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class LanggananObserver
{
    /**
     * Handle the Langganan "creating" event.
     * Logika ini berjalan SEBELUM langganan baru disimpan ke database.
     */
    public function creating(Langganan $langganan): void
    {
        // Cek duplikat berdasarkan id_pelanggan (prioritas utama)
        $dataTeknis = DataTeknis::where('pelanggan_id', $langganan->pelanggan_id)->first();
        $idPelangganToCheck = $dataTeknis ? $dataTeknis->id_pelanggan : null;

        if ($idPelangganToCheck) {
            $existingLanggananByIdPelanggan = Langganan::where('id_pelanggan', $idPelangganToCheck)
                                                ->where('user_status', 'Aktif')
                                                ->first();

            if ($existingLanggananByIdPelanggan) {
                $pelanggan = Pelanggan::find($langganan->pelanggan_id);
                $namaPelanggan = $pelanggan ? $pelanggan->nama : 'ID #' . $langganan->pelanggan_id;

                Log::warning('Mencoba membuat langganan duplikat berdasarkan id_pelanggan', [
                    'pelanggan_id' => $langganan->pelanggan_id,
                    'id_pelanggan' => $idPelangganToCheck,
                    'existing_langganan_id' => $existingLanggananByIdPelanggan->id,
                    'nama_pelanggan' => $namaPelanggan
                ]);

                throw ValidationException::withMessages([
                    'pelanggan_id' => ["Pelanggan dengan ID Pelanggan #{$idPelangganToCheck} sudah memiliki langganan aktif."],
                ]);
            }
        }

        // Cek duplikat berdasarkan pelanggan_id (jika id_pelanggan tidak ada)
        $existingLanggananByPelanggan = Langganan::where('pelanggan_id', $langganan->pelanggan_id)
                                          ->where('user_status', 'Aktif')
                                          ->first();

        if ($existingLanggananByPelanggan) {
            $pelanggan = Pelanggan::find($langganan->pelanggan_id);
            $namaPelanggan = $pelanggan ? $pelanggan->nama : 'ID #' . $langganan->pelanggan_id;

            Log::warning('Mencoba membuat langganan duplikat untuk pelanggan', [
                'pelanggan_id' => $langganan->pelanggan_id,
                'existing_langganan_id' => $existingLanggananByPelanggan->id,
                'nama_pelanggan' => $namaPelanggan
            ]);

            throw ValidationException::withMessages([
                'pelanggan_id' => ["Pelanggan ini sudah memiliki langganan aktif."],
            ]);
        }

        // Lanjutkan logika lain seperti sebelumnya...
        $pelanggan = Pelanggan::find($langganan->pelanggan_id);
        if ($pelanggan) {
            if (empty($langganan->id_brand) && !empty($pelanggan->id_brand)) {
                $langganan->id_brand = $pelanggan->id_brand;
            }
            if (empty($langganan->layanan) && !empty($pelanggan->layanan)) {
                $langganan->layanan = $pelanggan->layanan;
            }
        }

        if ($dataTeknis) {
            $langganan->profile_pppoe = $dataTeknis->profile_pppoe;
            $langganan->id_pelanggan = $dataTeknis->id_pelanggan;
            $langganan->olt = $dataTeknis->olt;
        }

        if ($langganan->id_brand) {
            $hargaLayanan = HargaLayanan::find($langganan->id_brand);
            if ($hargaLayanan) {
                if (!$langganan->layanan && $langganan->profile_pppoe) {
                    $matches = [];
                    if (preg_match('/(\d+)Mbps/', $langganan->profile_pppoe, $matches)) {
                        $langganan->layanan = $matches[1] . ' Mbps';
                    }
                }
                $langganan->hitungTotalHarga();
            }
        }

        if (is_null($langganan->tgl_jatuh_tempo)) {
            $langganan->setTanggalJatuhTempo();
        }

        // Set status awal menjadi Suspend, akan diaktifkan setelah pembayaran pertama
        $langganan->user_status = 'Suspend';
    }

    /**
     * Handle the Langganan "created" event.
     * Logika ini berjalan SETELAH langganan berhasil disimpan.
     */
    public function created(Langganan $langganan): void
    {
        $dataTeknis = DataTeknis::where('pelanggan_id', $langganan->pelanggan_id)->first();
        Log::info('Memeriksa Data Teknis setelah pembuatan langganan', [
            'langganan_id' => $langganan->id,
            'data_teknis_exists' => (bool)$dataTeknis,
        ]);

        if (!$dataTeknis) {
            // Terbitkan event jika data teknis belum ada
            event(new \App\Events\LanggananCreatedWithoutDataTeknis($langganan));
            Log::info('Event LanggananCreatedWithoutDataTeknis dipicu', ['langganan_id' => $langganan->id]);
        }
    }

    /**
     * Handle the Langganan "updated" event.
     */
    public function updated(Langganan $langganan): void
    {
        // Logika ini sudah dipindahkan ke dalam method boot() di model Langganan
        // untuk menerbitkan event LanggananStatusChanged.
        // Anda bisa menambahkan logika lain di sini jika diperlukan.
    }

    /**
     * Handle the Langganan "deleted" event.
     */
    public function deleted(Langganan $langganan): void
    {
        // Logika jika diperlukan saat langganan dihapus
    }

    /**
     * Handle the Langganan "restored" event.
     */
    public function restored(Langganan $langganan): void
    {
        //
    }

    /**
     * Handle the Langganan "force deleted" event.
     */
    public function forceDeleted(Langganan $langganan): void
    {
        //
    }
}
