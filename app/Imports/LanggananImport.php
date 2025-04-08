<?php

namespace App\Imports;

use App\Models\Langganan;
use App\Models\DataTeknis;
use App\Models\HargaLayanan;
use App\Models\Pelanggan;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithPreparation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class LanggananImport implements ToModel, WithHeadingRow, WithValidation
{
    protected $importedIds = [];
    protected $defaultBrandId;
    
    public function __construct()
    {
        // Ambil ID brand pertama yang ada di database saat inisialisasi
        $this->defaultBrandId = $this->getDefaultBrandId();
        Log::info('Default Brand ID set to', ['id' => $this->defaultBrandId]);
    }
    
    /**
     * Mendapatkan ID brand default yang valid dari database
     */
    protected function getDefaultBrandId()
    {
        $brand = HargaLayanan::first();
        if ($brand) {
            return $brand->id_brand;
        }
        
        // Jika tidak ada brand di database, log warning
        Log::warning('Tidak ada harga layanan tersedia di database');
        return null;
    }
    
    /**
     * Prepare data before validation
     */
    public function prepareForValidation(array $row, int $index)
    {
        // Jika id_brand kosong atau tidak ada, gunakan default
        if (!isset($row['id_brand']) || empty($row['id_brand'])) {
            if ($this->defaultBrandId) {
                $row['id_brand'] = $this->defaultBrandId;
                Log::info('Menggunakan ID brand default', ['row' => $index + 2, 'id_brand' => $this->defaultBrandId]);
            } else {
                // Jika tidak ada default brand, skip validasi ini dengan mencatat warning
                Log::warning('Tidak ada brand default tersedia untuk baris', ['row' => $index + 2]);
            }
        } else {
            // Verifikasi apakah id_brand yang ada di data valid
            $exists = HargaLayanan::where('id_brand', $row['id_brand'])->exists();
            if (!$exists && $this->defaultBrandId) {
                Log::warning('ID brand tidak valid, menggunakan default', [
                    'row' => $index + 2, 
                    'invalid_id' => $row['id_brand'], 
                    'default_id' => $this->defaultBrandId
                ]);
                $row['id_brand'] = $this->defaultBrandId;
            }
        }
        
        // Pastikan layanan juga memiliki nilai default jika kosong
        if (!isset($row['layanan']) || empty($row['layanan'])) {
            $row['layanan'] = '10 Mbps';
        }
        
        // Standardisasi status user
        if (isset($row['user_status'])) {
            $row['user_status'] = $this->normalizeUserStatus($row['user_status']);
        } else {
            // Jika kolom tidak ada atau kosong, default ke Aktif
            $row['user_status'] = 'Aktif';
        }
        
        return $row;
    }
    
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

            // Cek apakah harga layanan ada - dengan error handling lebih baik
            $hargaLayanan = HargaLayanan::where('id_brand', $row['id_brand'])->first();
            if (!$hargaLayanan) {
                Log::error('Brand layanan tidak ditemukan', ['id_brand' => $row['id_brand']]);
                // Coba cari harga layanan default
                $hargaLayanan = HargaLayanan::first();
                if (!$hargaLayanan) {
                    Log::error('Tidak ada brand layanan tersedia di database');
                    return null;
                }
                Log::info('Menggunakan brand layanan default', ['id_brand' => $hargaLayanan->id_brand]);
                // Update row id_brand agar valid untuk validasi selanjutnya
                $row['id_brand'] = $hargaLayanan->id_brand;
            }

            // Transformasi tanggal jatuh tempo - biarkan NULL jika kosong
            $tglJatuhTempo = null;
            if (isset($row['tgl_jatuh_tempo']) && !empty($row['tgl_jatuh_tempo'])) {
                $tglJatuhTempo = $this->transformDate($row['tgl_jatuh_tempo']);
            }
            
            // Siapkan data untuk model
            $data = [
                'pelanggan_id' => $row['pelanggan_id'],
                'id_brand' => $hargaLayanan->id_brand, // Gunakan id dari objek yang sudah dicek
                'layanan' => $row['layanan'] ?? '10 Mbps',
                'tgl_jatuh_tempo' => $tglJatuhTempo, // Bisa NULL
                'metode_pembayaran' => $row['metode_pembayaran'] ?? 'otomatis',
                'user_status' => $row['user_status'] ?? 'Aktif',
                // Gunakan data dari data_teknis untuk memastikan nilai yang valid
                'profile_pppoe' => $dataTeknis->profile_pppoe,
                'olt' => $dataTeknis->olt,
                'id_pelanggan' => $dataTeknis->id_pelanggan,
            ];
            
           // Hitung total harga berdasarkan layanan dan brand - dengan validasi lebih baik
            $harga = match ($data['layanan']) {
                '10 Mbps' => $hargaLayanan->harga_10mbps ?? 0,
                '20 Mbps' => $hargaLayanan->harga_20mbps ?? 0,
                '30 Mbps' => $hargaLayanan->harga_30mbps ?? 0,
                '50 Mbps' => $hargaLayanan->harga_50mbps ?? 0,
                default => $hargaLayanan->harga_10mbps ?? 0, // Default ke 10Mbps jika tidak dikenali
            };

            $pajak = (($hargaLayanan->pajak ?? 0) / 100) * $harga;
            $total = $harga + $pajak;
            $data['total_harga_layanan_x_pajak'] = ceil($total / 1000) * 1000; // Pembulatan ke ribuan terdekat
            
            Log::info('Harga dihitung', [
                'layanan' => $data['layanan'],
                'harga_dasar' => $harga,
                'pajak' => $pajak,
                'total_harga' => $data['total_harga_layanan_x_pajak']
            ]);
            
            // Cek apakah langganan sudah ada untuk pelanggan ini
            $existingLangganan = DB::table('langganan')
                ->where('pelanggan_id', $data['pelanggan_id'])
                ->first();
                
            if ($existingLangganan) {
                // Update langganan yang sudah ada - buat array untuk update
                $updateData = [
                    'id_brand' => $data['id_brand'],
                    'layanan' => $data['layanan'],
                    'metode_pembayaran' => $data['metode_pembayaran'],
                    'user_status' => $data['user_status'],
                    'profile_pppoe' => $data['profile_pppoe'],
                    'olt' => $data['olt'],
                    'id_pelanggan' => $data['id_pelanggan'],
                    'total_harga_layanan_x_pajak' => $data['total_harga_layanan_x_pajak'],
                    'updated_at' => now(),
                ];
                
                // Hanya tambahkan tgl_jatuh_tempo jika tidak kosong
                if ($data['tgl_jatuh_tempo'] !== null) {
                    $updateData['tgl_jatuh_tempo'] = $data['tgl_jatuh_tempo'];
                }
                
                DB::table('langganan')
                    ->where('id', $existingLangganan->id)
                    ->update($updateData);
                    
                $this->importedIds[] = $existingLangganan->id;
                
                Log::info('Data langganan berhasil diupdate', [
                    'id' => $existingLangganan->id,
                    'pelanggan_id' => $data['pelanggan_id']
                ]);
            } else {
                // Buat langganan baru - buat array untuk insert
                $insertData = [
                    'pelanggan_id' => $data['pelanggan_id'],
                    'id_brand' => $data['id_brand'],
                    'layanan' => $data['layanan'],
                    'metode_pembayaran' => $data['metode_pembayaran'],
                    'user_status' => $data['user_status'],
                    'profile_pppoe' => $data['profile_pppoe'],
                    'olt' => $data['olt'],
                    'id_pelanggan' => $data['id_pelanggan'],
                    'total_harga_layanan_x_pajak' => $data['total_harga_layanan_x_pajak'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                
                // Hanya tambahkan tgl_jatuh_tempo jika tidak kosong
                if ($data['tgl_jatuh_tempo'] !== null) {
                    $insertData['tgl_jatuh_tempo'] = $data['tgl_jatuh_tempo'];
                }
                
                $id = DB::table('langganan')->insertGetId($insertData);
                
                $this->importedIds[] = $id;
                
                Log::info('Data langganan baru berhasil dibuat', [
                    'id' => $id,
                    'pelanggan_id' => $data['pelanggan_id']
                ]);
            }
            
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
        // Mungkin perlu melakukan query untuk mendapatkan semua ID brand yang valid
        $validBrands = HargaLayanan::pluck('id_brand')->toArray();
        
        return [
            'pelanggan_id' => 'required|exists:pelanggan,id',
            // Pastikan id_brand valid tapi bisa null
            'id_brand' => ['nullable', function ($attribute, $value, $fail) use ($validBrands) {
                if ($value && !in_array($value, $validBrands)) {
                    // Jangan gagalkan validasi, kita akan atur di prepareForValidation
                    // $fail("ID brand tidak valid.");
                }
            }],
            'layanan' => 'nullable',
            'tgl_jatuh_tempo' => 'nullable',
            'user_status' => 'nullable',
            'metode_pembayaran' => 'nullable',
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
        if (!is_string($status)) {
            return 'Aktif';
        }
        
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
        if (empty($value)) {
            return null; // Kembalikan NULL untuk tanggal kosong
        }
        
        try {
            // Coba parse dengan beberapa format umum
            foreach(['m/d/Y', 'd/m/Y', 'm-d-Y', 'd-m-Y', 'Y-m-d'] as $format) {
                try {
                    return Carbon::createFromFormat($format, $value);
                } catch (\Exception $e) {
                    continue;
                }
            }
            
            // Coba parse secara umum
            return Carbon::parse($value);
        } catch (\Exception $e) {
            Log::warning('Format tanggal tidak valid, mengembalikan null', ['value' => $value]);
            return null; // Tetap kembalikan NULL jika format tanggal tidak valid
        }
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            foreach ($validator->getData() as $rowIndex => $row) {
                if (isset($row['tgl_jatuh_tempo']) && !empty($row['tgl_jatuh_tempo'])) {
                    try {
                        // Coba parse tanggal untuk memastikan valid
                        Carbon::parse($row['tgl_jatuh_tempo']);
                    } catch (\Exception $e) {
                        // Jika parse gagal, tambahkan error
                        $validator->errors()->add($rowIndex . '.tgl_jatuh_tempo', 
                            'Format tanggal tidak valid. Gunakan format yang bisa dikenali Carbon.');
                    }
                }
            }
        });
    }
    
    /**
     * Mendapatkan ID yang berhasil diimpor
     */
    public function getImportedIds()
    {
        return $this->importedIds;
    }
}