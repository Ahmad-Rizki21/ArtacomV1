<?php

namespace App\Imports;

use App\Models\Langganan;
use App\Models\DataTeknis;
use App\Models\HargaLayanan;
use App\Models\Pelanggan;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class LanggananImport implements ToCollection, WithHeadingRow
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
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
        Log::info('Starting langganan import with ' . $rows->count() . ' rows');
        
        $successCount = 0;
        $failedCount = 0;
        
        // Deactivate foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        try {
            // Process each row individually to avoid batch failures
            foreach ($rows as $index => $row) {
                try {
                    // Log raw data for debugging
                    Log::debug('Langganan raw data row ' . ($index + 2), (array)$row);
                    
                    // Skip if row is empty
                    if (empty($row) || count(array_filter((array)$row)) === 0) {
                        Log::info('Skipping empty row ' . ($index + 2));
                        continue;
                    }
                    
                    // Extract and validate pelanggan_id
                    $pelangganId = isset($row['pelanggan_id']) ? (int)$row['pelanggan_id'] : null;
                    
                    // Skip if no pelanggan_id
                    if (!$pelangganId) {
                        Log::warning('Skipping row ' . ($index + 2) . ': No pelanggan_id found');
                        $failedCount++;
                        continue;
                    }
                    
                    // Check if pelanggan exists, create if not
                    $pelangganExists = DB::table('pelanggan')->where('id', $pelangganId)->exists();
                    if (!$pelangganExists) {
                        // Try to create minimal record if we have a name
                        $pelangganName = isset($row['id_pelanggan']) ? (string)$row['id_pelanggan'] : ('Pelanggan ' . $pelangganId);
                        
                        Log::warning('Pelanggan ID ' . $pelangganId . ' not found. Creating minimal record with name: ' . $pelangganName);
                        
                        DB::table('pelanggan')->insert([
                            'id' => $pelangganId,
                            'nama' => $pelangganName,
                            'no_ktp' => '0000000000000000',
                            'alamat' => 'Generated',
                            'blok' => 'N/A',
                            'unit' => 'N/A',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                    
                    // Check/create data_teknis if needed
                    $dataTeknis = DB::table('data_teknis')->where('pelanggan_id', $pelangganId)->first();
                    if (!$dataTeknis) {
                        // Create minimal data_teknis record
                        $idPelanggan = isset($row['id_pelanggan']) ? (string)$row['id_pelanggan'] : ('DT-' . $pelangganId);
                        
                        Log::warning('Data teknis for pelanggan ID ' . $pelangganId . ' not found. Creating minimal record');
                        
                        $dataTeknisId = DB::table('data_teknis')->insertGetId([
                            'pelanggan_id' => $pelangganId,
                            'id_vlan' => '10',
                            'id_pelanggan' => $idPelanggan,
                            'password_pppoe' => 'support123.!!',
                            'ip_pelanggan' => '192.168.0.' . $pelangganId,
                            'profile_pppoe' => '10Mbps-a',
                            'olt' => 'Default',
                            'olt_custom' => 'N/A',
                            'pon' => 0,
                            'otb' => 0,
                            'odc' => 0,
                            'odp' => 0,
                            'onu_power' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        
                        // Get the created record
                        $dataTeknis = DB::table('data_teknis')->find($dataTeknisId);
                    }
                    
                    // Handle brand
                    $brandId = isset($row['id_brand']) && !empty($row['id_brand']) ? (string)$row['id_brand'] : $this->defaultBrandId;
                    
                    // Check if brand exists
                    $brandExists = $brandId ? DB::table('harga_layanan')->where('id_brand', $brandId)->exists() : false;
                    if (!$brandExists) {
                        Log::warning('Brand ID ' . $brandId . ' not found. Using default brand: ' . $this->defaultBrandId);
                        $brandId = $this->defaultBrandId;
                    }
                    
                    // Prepare layanan value
                    $layanan = isset($row['layanan']) && !empty($row['layanan']) ? (string)$row['layanan'] : '10 Mbps';
                    
                    // Parse tanggal jatuh tempo
                    $tglJatuhTempo = null;
                    if (isset($row['tgl_jatuh_tempo']) && !empty($row['tgl_jatuh_tempo'])) {
                        try {
                            $tglJatuhTempo = $this->transformDate($row['tgl_jatuh_tempo']);
                        } catch (\Exception $e) {
                            Log::warning('Invalid date format for tgl_jatuh_tempo: ' . $row['tgl_jatuh_tempo']);
                        }
                    }
                    
                    // Normalize user status
                    $userStatus = isset($row['user_status']) ? $this->normalizeUserStatus($row['user_status']) : 'Aktif';
                    
                    // Calculate price
                    $hargaLayanan = DB::table('harga_layanan')->where('id_brand', $brandId)->first();
                    $hargaValue = 0;
                    $pajakValue = 0;
                    
                    if ($hargaLayanan) {
                        // Get price based on layanan
                        switch ($layanan) {
                            case '10 Mbps':
                                $hargaValue = $hargaLayanan->harga_10mbps ?? 0;
                                break;
                            case '20 Mbps':
                                $hargaValue = $hargaLayanan->harga_20mbps ?? 0;
                                break;
                            case '30 Mbps':
                                $hargaValue = $hargaLayanan->harga_30mbps ?? 0;
                                break;
                            case '50 Mbps':
                                $hargaValue = $hargaLayanan->harga_50mbps ?? 0;
                                break;
                            default:
                                $hargaValue = $hargaLayanan->harga_10mbps ?? 0;
                        }
                        
                        // Calculate tax
                        $pajakValue = (($hargaLayanan->pajak ?? 0) / 100) * $hargaValue;
                    }
                    
                    $totalHarga = ceil(($hargaValue + $pajakValue) / 1000) * 1000;
                    
                    // Prepare data for insert/update
                    $data = [
                        'pelanggan_id' => $pelangganId,
                        'id_brand' => $brandId,
                        'layanan' => $layanan,
                        'total_harga_layanan_x_pajak' => $totalHarga,
                        'tgl_jatuh_tempo' => $tglJatuhTempo,
                        'metode_pembayaran' => isset($row['metode_pembayaran']) ? (string)$row['metode_pembayaran'] : 'otomatis',
                        'user_status' => $userStatus,
                        'profile_pppoe' => $dataTeknis->profile_pppoe,
                        'olt' => $dataTeknis->olt,
                        'id_pelanggan' => $dataTeknis->id_pelanggan,
                        'updated_at' => now(),
                    ];
                    
                    Log::info('Processing langganan data', [
                        'pelanggan_id' => $pelangganId,
                        'brand' => $brandId,
                        'layanan' => $layanan,
                        'total_harga' => $totalHarga
                    ]);
                    
                    // Check if record already exists
                    $existingRecord = DB::table('langganan')->where('pelanggan_id', $pelangganId)->first();
                    
                    if ($existingRecord) {
                        // Update existing record
                        DB::table('langganan')
                            ->where('id', $existingRecord->id)
                            ->update($data);
                            
                        $this->importedIds[] = $existingRecord->id;
                        
                        Log::info('Updated langganan for pelanggan ID ' . $pelangganId);
                    } else {
                        // Insert new record
                        $data['created_at'] = now();
                        
                        // Use ID from Excel if available
                        if (isset($row['id']) && is_numeric($row['id'])) {
                            $data['id'] = (int)$row['id'];
                        }
                        
                        $id = DB::table('langganan')->insertGetId($data);
                        
                        $this->importedIds[] = $id;
                        
                        Log::info('Inserted new langganan for pelanggan ID ' . $pelangganId);
                    }
                    
                    $successCount++;
                    
                } catch (\Exception $e) {
                    // Log error but continue with next row
                    Log::error('Error processing langganan row ' . ($index + 2), [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    
                    $failedCount++;
                }
            }
        } finally {
            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
        
        Log::info('Langganan import completed', [
            'total' => $rows->count(),
            'success' => $successCount,
            'failed' => $failedCount,
            'imported_ids' => $this->importedIds
        ]);
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
    
    /**
     * Mendapatkan ID yang berhasil diimpor
     */
    public function getImportedIds()
    {
        return $this->importedIds;
    }
}