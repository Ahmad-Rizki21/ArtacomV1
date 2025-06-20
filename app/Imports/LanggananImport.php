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
        $this->defaultBrandId = $this->getDefaultBrandId();
        Log::info('Default Brand ID set to', ['id' => $this->defaultBrandId]);
    }
    
    protected function getDefaultBrandId()
    {
        $brand = HargaLayanan::first();
        if ($brand) {
            return $brand->id_brand;
        }
        Log::warning('Tidak ada harga layanan tersedia di database');
        return null;
    }

    /**
     * Konversi Excel serial date ke Carbon
     */
    protected function excelDateToCarbon($value)
    {
        if (is_numeric($value)) {
            // Excel serial date offset 25569, 86400 detik per hari
            $timestamp = ($value - 25569) * 86400;
            return Carbon::createFromTimestamp($timestamp)->startOfDay();
        }

        try {
            return Carbon::parse($value);
        } catch (\Exception $e) {
            Log::warning('Tanggal tidak valid saat parsing: ' . $value);
            return null;
        }
    }
    
    public function collection(Collection $rows)
    {
        Log::info('Starting langganan import with ' . $rows->count() . ' rows');
        
        $successCount = 0;
        $failedCount = 0;
        
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        try {
            foreach ($rows as $index => $row) {
                try {
                    Log::debug('Langganan raw data row ' . ($index + 2), (array)$row);
                    
                    if (empty($row) || count(array_filter((array)$row)) === 0) {
                        Log::info('Skipping empty row ' . ($index + 2));
                        continue;
                    }
                    
                    $pelangganId = isset($row['pelanggan_id']) ? (int)$row['pelanggan_id'] : null;
                    if (!$pelangganId) {
                        Log::warning('Skipping row ' . ($index + 2) . ': No pelanggan_id found');
                        $failedCount++;
                        continue;
                    }
                    
                    $pelangganExists = DB::table('pelanggan')->where('id', $pelangganId)->exists();
                    if (!$pelangganExists) {
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

                    $dataTeknis = DB::table('data_teknis')->where('pelanggan_id', $pelangganId)->first();
                    if (!$dataTeknis) {
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
                        $dataTeknis = DB::table('data_teknis')->find($dataTeknisId);
                    }

                    $brandId = isset($row['id_brand']) && !empty($row['id_brand']) ? (string)$row['id_brand'] : $this->defaultBrandId;
                    $brandExists = $brandId ? DB::table('harga_layanan')->where('id_brand', $brandId)->exists() : false;
                    if (!$brandExists) {
                        Log::warning('Brand ID ' . $brandId . ' not found. Using default brand: ' . $this->defaultBrandId);
                        $brandId = $this->defaultBrandId;
                    }

                    $layanan = isset($row['layanan']) && !empty($row['layanan']) ? (string)$row['layanan'] : '10 Mbps';

                    // Parse tanggal dengan konversi Excel serial date
                    $tglJatuhTempo = null;
                    if (isset($row['tgl_jatuh_tempo']) && !empty($row['tgl_jatuh_tempo'])) {
                        $tglJatuhTempo = $this->excelDateToCarbon($row['tgl_jatuh_tempo']);
                    }

                    $tglInvoiceTerakhir = null;
                    if (isset($row['tgl_invoice_terakhir']) && !empty($row['tgl_invoice_terakhir'])) {
                        $tglInvoiceTerakhir = $this->excelDateToCarbon($row['tgl_invoice_terakhir']);
                    }

                    $userStatus = isset($row['user_status']) ? $this->normalizeUserStatus($row['user_status']) : 'Aktif';

                    $hargaLayanan = DB::table('harga_layanan')->where('id_brand', $brandId)->first();
                    $hargaValue = 0;
                    $pajakValue = 0;

                    if ($hargaLayanan) {
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
                        $pajakValue = (($hargaLayanan->pajak ?? 0) / 100) * $hargaValue;
                    }

                    $totalHarga = ceil(($hargaValue + $pajakValue) / 1000) * 1000;

                    $data = [
                        'pelanggan_id' => $pelangganId,
                        'id_brand' => $brandId,
                        'layanan' => $layanan,
                        'total_harga_layanan_x_pajak' => $totalHarga,
                        'tgl_jatuh_tempo' => $tglJatuhTempo ? $tglJatuhTempo->format('Y-m-d') : null,
                        'metode_pembayaran' => isset($row['metode_pembayaran']) ? (string)$row['metode_pembayaran'] : 'otomatis',
                        'user_status' => $userStatus,
                        'profile_pppoe' => $dataTeknis->profile_pppoe,
                        'olt' => $dataTeknis->olt,
                        'id_pelanggan' => $dataTeknis->id_pelanggan,
                        'tgl_invoice_terakhir' => $tglInvoiceTerakhir ? $tglInvoiceTerakhir->format('Y-m-d') : null,
                        'updated_at' => now(),
                    ];

                    Log::info('Processing langganan data', [
                        'pelanggan_id' => $pelangganId,
                        'brand' => $brandId,
                        'layanan' => $layanan,
                        'total_harga' => $totalHarga,
                        'tgl_jatuh_tempo' => $data['tgl_jatuh_tempo'],
                        'tgl_invoice_terakhir' => $data['tgl_invoice_terakhir']
                    ]);

                    $existingRecord = DB::table('langganan')->where('pelanggan_id', $pelangganId)->first();

                    if ($existingRecord) {
                        DB::table('langganan')->where('id', $existingRecord->id)->update($data);
                        $this->importedIds[] = $existingRecord->id;
                        Log::info('Updated langganan for pelanggan ID ' . $pelangganId);
                    } else {
                        $data['created_at'] = now();
                        if (isset($row['id']) && is_numeric($row['id'])) {
                            $data['id'] = (int)$row['id'];
                        }
                        $id = DB::table('langganan')->insertGetId($data);
                        $this->importedIds[] = $id;
                        Log::info('Inserted new langganan for pelanggan ID ' . $pelangganId);
                    }

                    $successCount++;
                } catch (\Exception $e) {
                    Log::error('Error processing langganan row ' . ($index + 2), [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    $failedCount++;
                }
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        Log::info('Langganan import completed', [
            'total' => $rows->count(),
            'success' => $successCount,
            'failed' => $failedCount,
            'imported_ids' => $this->importedIds
        ]);
    }

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
        return 'Aktif';
    }
    
    public function getImportedIds()
    {
        return $this->importedIds;
    }
}
