<?php

namespace App\Imports;

use App\Models\Pelanggan;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithoutTransaction;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PelangganImport implements ToCollection, WithHeadingRow, WithCustomCsvSettings, SkipsEmptyRows
{
    public function getCsvSettings(): array
    {
        return [
            'delimiter' => ';',
            'enclosure' => '"',
            'escape_character' => '\\',
            'input_encoding' => 'UTF-8',
            'use_bom' => true
        ];
    }

    /**
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
        Log::info('Starting import with ' . $rows->count() . ' rows');
        
        // Debug headers untuk melihat masalah mapping kolom
        if ($rows->count() > 0) {
            Log::info('Headers detected:', [
                'headers' => is_array($rows->first()) ? array_keys($rows->first()) : (is_object($rows->first()) ? array_keys($rows->first()->toArray()) : 'No headers found')
            ]);
            Log::info('First row data:', [
                'data' => is_array($rows->first()) ? $rows->first() : (is_object($rows->first()) ? $rows->first()->toArray() : 'No data found')
            ]);
        }
        
        $successCount = 0;
        $dataToInsert = [];
        
        foreach ($rows as $index => $row) {
            try {
                // Skip jika row adalah header atau kosong
                if ($index === 0 || empty($row) || count(array_filter((array)$row)) === 0) {
                    continue;
                }
                
                // Deteksi apakah kolom 'id' ada dan hilangkan jika perlu 
                // (ini untuk menangani file yang memiliki kolom 'id' di awal)
                if (isset($row['id'])) {
                    Log::info('ID column found in row ' . $index . ': ' . $row['id']);
                }
                
                // Format no_ktp
                $noKtp = "00000000";
                if (isset($row['no_ktp'])) {
                    // Pastikan nilai valid
                    if (!empty($row['no_ktp'])) {
                        if (is_numeric($row['no_ktp'])) {
                            $noKtp = str_pad($row['no_ktp'], 8, "0", STR_PAD_LEFT);
                        } else {
                            $noKtp = (string)$row['no_ktp'];
                        }
                    }
                }
                
                // Format phone numbers
                $noTelp = "";
                if (isset($row['no_telp'])) {
                    // Hapus karakter non-digit
                    $cleaned = preg_replace('/[^0-9]/', '', (string)$row['no_telp']);
                    
                    // Pastikan diawali 0
                    $noTelp = substr($cleaned, 0, 1) !== '0' 
                        ? '0' . $cleaned 
                        : $cleaned;
                }
                
                // ALAMAT FIX - Check all possible patterns carefully
                $alamat = "";
                if (isset($row['alamat'])) {
                    $alamatValue = (string)$row['alamat'];
                    
                    // Direct replacement for any "Perumahan n" patterns
                    if (strpos($alamatValue, 'Perumahan n ') !== false) {
                        $alamat = str_replace('Perumahan n ', 'Perumahan ', $alamatValue);
                    }
                    // Excel truncated patterns
                    else if (strpos($alamatValue, 'Perumaha') === 0) {
                        if (preg_match('/PerumahaC/i', $alamatValue)) {
                            $alamat = "Perumahan Tambun";
                        } else if (preg_match('/PerumahaN\/A/i', $alamatValue)) {
                            $alamat = "Perumahan Tambun";
                        } else if (preg_match('/PerumahaB/i', $alamatValue)) {
                            $alamat = "Perumahan Waringin";
                        } else if (preg_match('/PerumahaA/i', $alamatValue)) {
                            $alamat = "Perumahan Waringin";
                        } else {
                            $alamat = str_replace("Perumaha", "Perumahan ", $alamatValue);
                        }
                    } else {
                        $alamat = $alamatValue;
                    }
                    
                    // Final safety check to ensure no "n" remains
                    if (strpos($alamat, 'Perumahan n') !== false) {
                        $alamat = str_replace('Perumahan n', 'Perumahan', $alamat);
                    }
                }
                
                // Process other fields
                $nama = isset($row['nama']) ? (string)$row['nama'] : ""; //Memuat data nama dari Excel
                $blok = isset($row['blok']) ? (string)$row['blok'] : "N/A"; //Memuat data blok dari Excel
                $unit = isset($row['unit']) ? (string)$row['unit'] : "N/A"; //Memuat data unit dari Excel
                $email = isset($row['email']) ? (string)$row['email'] : "N/A"; //Memuat data email dari Excel
                $alamat2 = isset($row['alamat_2']) ? (string)$row['alamat_2'] : ""; //Memuat data alamat_2 dari Excel
                
                // Proses kolom tambahan jika ada
                $tglInstalasi = null;
                if (isset($row['tgl_instalasi']) && !empty($row['tgl_instalasi'])) {
                    try {
                        $date = \Carbon\Carbon::parse($row['tgl_instalasi']);
                        $tglInstalasi = $date->format('Y-m-d');
                    } catch (\Exception $e) {
                        Log::warning('Error parsing date: ' . $row['tgl_instalasi']);
                    }
                }
                
                $idBrand = isset($row['id_brand']) && !empty($row['id_brand']) ? (string)$row['id_brand'] : null;
                $layanan = isset($row['layanan']) && !empty($row['layanan']) ? (string)$row['layanan'] : null;
                $brandDefault = isset($row['brand_default']) && !empty($row['brand_default']) ? (string)$row['brand_default'] : null;
                $alamatCustom = isset($row['alamat_custom']) && !empty($row['alamat_custom']) ? (string)$row['alamat_custom'] : null;
                
                // For debugging
                Log::debug('Processing row ' . ($index + 1), [
                    'nama' => $nama,
                    'no_ktp' => $noKtp,
                    'alamat' => $alamat,
                    'tgl_instalasi' => $tglInstalasi,
                    'id_brand' => $idBrand,
                    'layanan' => $layanan
                ]);
                
                // Prepare data for insert
                $dataToInsert[] = [
                    'no_ktp' => $noKtp,
                    'nama' => $nama,
                    'alamat' => $alamat,
                    'alamat_custom' => $alamatCustom,
                    'tgl_instalasi' => $tglInstalasi,
                    'blok' => $blok,
                    'unit' => $unit,
                    'no_telp' => $noTelp,
                    'email' => $email,
                    'id_brand' => $idBrand,
                    'layanan' => $layanan,
                    'brand_default' => $brandDefault,
                    'alamat_2' => $alamat2,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
                
                $successCount++;
                
                // Insert in batches
                if (count($dataToInsert) >= 100) {
                    DB::table('pelanggan')->insert($dataToInsert);
                    $dataToInsert = [];
                }
                
            } catch (\Exception $e) {
                Log::error('Error processing row ' . ($index + 1), [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'row' => isset($row) ? json_encode((array)$row) : 'No data'
                ]);
            }
        }
        
        // Insert any remaining data
        if (!empty($dataToInsert)) {
            DB::table('pelanggan')->insert($dataToInsert);
        }
        
        // Fix any remaining "Perumahan n" issues in the newly imported data
        $this->fixAddressesInDatabase();
        
        Log::info('Import completed', [
            'total' => $rows->count(),
            'success' => $successCount,
            'failed' => $rows->count() - $successCount
        ]);
    }
    
    /**
     * Fix addresses in the database
     */
    private function fixAddressesInDatabase()
    {
        try {
            // Multiple passes to ensure all variants are fixed
            DB::statement("UPDATE pelanggan SET alamat = REPLACE(alamat, 'Perumahan n ', 'Perumahan ') WHERE alamat LIKE 'Perumahan n %'");
            DB::statement("UPDATE pelanggan SET alamat = REPLACE(alamat, 'Perumahan n', 'Perumahan') WHERE alamat LIKE 'Perumahan n%'");
            
            Log::info('Fixed addresses in database');
        } catch (\Exception $e) {
            Log::error('Error fixing addresses', [
                'error' => $e->getMessage()
            ]);
        }
    }
}