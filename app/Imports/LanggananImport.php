<?php

namespace App\Imports;

use App\Models\Langganan;
use App\Models\DataTeknis;
use App\Models\HargaLayanan;
use App\Models\Pelanggan;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class LanggananImport implements ToModel, WithHeadingRow, WithValidation
{
    protected $importedIds = [];
    
    /**
     * @param array $row
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Log data impor untuk debugging
        Log::info('Importing row', ['data' => $row]);

        try {
            // Cek apakah pelanggan ada
            $pelanggan = Pelanggan::find($row['pelanggan_id']);
            if (!$pelanggan) {
                Log::error('Pelanggan tidak ditemukan', ['pelanggan_id' => $row['pelanggan_id']]);
                return null;
            }

            // Ambil data teknis berdasarkan pelanggan_id
            $dataTeknis = DataTeknis::where('pelanggan_id', $row['pelanggan_id'])->first();
            if (!$dataTeknis) {
                Log::error('Data teknis tidak ditemukan untuk pelanggan', ['pelanggan_id' => $row['pelanggan_id']]);
                return null;
            }

            // Cek apakah harga layanan ada
            $hargaLayanan = HargaLayanan::find($row['id_brand']);
            if (!$hargaLayanan) {
                Log::error('Brand layanan tidak ditemukan', ['id_brand' => $row['id_brand']]);
                return null;
            }

            // Normalisasi status pengguna
            $userStatus = $this->normalizeUserStatus($row['user_status'] ?? 'Aktif');

            // Siapkan data untuk model
            $data = [
                'pelanggan_id' => $row['pelanggan_id'],
                'id_brand' => $row['id_brand'],
                'layanan' => $row['layanan'] ?? '10 Mbps',
                'tgl_jatuh_tempo' => $this->transformDate($row['tgl_jatuh_tempo']),
                'metode_pembayaran' => $row['metode_pembayaran'] ?? 'otomatis',
                'user_status' => $userStatus,
                // Gunakan data dari data_teknis untuk memastikan nilai yang valid
                'profile_pppoe' => $dataTeknis->profile_pppoe,
                'olt' => $dataTeknis->olt,
                'id_pelanggan' => $dataTeknis->id_pelanggan,
            ];
            
            // Hitung total harga berdasarkan layanan dan brand
            $harga = match ($data['layanan']) {
                '10 Mbps' => $hargaLayanan->harga_10mbps,
                '20 Mbps' => $hargaLayanan->harga_20mbps,
                '30 Mbps' => $hargaLayanan->harga_30mbps,
                '50 Mbps' => $hargaLayanan->harga_50mbps,
                default => 0,
            };

            $pajak = ($hargaLayanan->pajak / 100) * $harga;
            $data['total_harga_layanan_x_pajak'] = $harga + $pajak;
            
            Log::info('Harga dihitung', [
                'layanan' => $data['layanan'],
                'harga_dasar' => $harga,
                'pajak' => $pajak,
                'total_harga' => $data['total_harga_layanan_x_pajak']
            ]);
            
            // Simpan langsung ke database menggunakan query builder untuk melewati model events
            $id = DB::table('langganan')->insertGetId([
                'pelanggan_id' => $data['pelanggan_id'],
                'id_brand' => $data['id_brand'],
                'layanan' => $data['layanan'],
                'tgl_jatuh_tempo' => $data['tgl_jatuh_tempo'],
                'metode_pembayaran' => $data['metode_pembayaran'],
                'user_status' => $data['user_status'],
                'profile_pppoe' => $data['profile_pppoe'],
                'olt' => $data['olt'],
                'id_pelanggan' => $data['id_pelanggan'],
                'total_harga_layanan_x_pajak' => $data['total_harga_layanan_x_pajak'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            $this->importedIds[] = $id;
            
            Log::info('Data langganan berhasil diimpor', [
                'id' => $id,
                'pelanggan_id' => $data['pelanggan_id'],
                'status' => $data['user_status']
            ]);
            
            // Return null karena sudah simpan manual
            return null;
            
        } catch (\Exception $e) {
            Log::error('Error saat memproses baris import', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'row' => $row
            ]);
            
            throw $e;
        }
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'pelanggan_id' => 'required|exists:pelanggan,id',
            'id_brand' => 'required|exists:harga_layanan,id_brand',
            'layanan' => 'required',
            'tgl_jatuh_tempo' => 'required|date',
            // Hapus validasi terlalu ketat untuk status dan metode pembayaran
        ];
    }
    
    /**
     * Normalize user status to ensure consistent values
     * 
     * @param string $status
     * @return string
     */
    protected function normalizeUserStatus($status)
    {
        $status = trim(strtolower($status));
        
        if ($status === 'aktif' || $status === 'active') {
            return 'Aktif';
        }
        
        if ($status === 'suspend' || $status === 'suspended' || $status === 'nonaktif') {
            return 'Suspend';
        }
        
        // Default to Aktif if status unrecognized
        return 'Aktif';
    }

    /**
     * Transform a date value into a Carbon object.
     *
     * @return \Carbon\Carbon|null
     */
    public function transformDate($value)
    {
        try {
            return Carbon::parse($value);
        } catch (\Exception $e) {
            Log::warning('Format tanggal tidak valid, menggunakan tanggal sekarang', ['value' => $value]);
            return now();
        }
    }
    
    /**
     * Mendapatkan ID yang berhasil diimpor
     */
    public function getImportedIds()
    {
        return $this->importedIds;
    }
}