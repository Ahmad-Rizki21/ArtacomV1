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
            'delimiter' => ',',
            'enclosure' => '"',
            'escape_character' => '\\',
            'input_encoding' => 'UTF-8'
        ];
    }

    /**
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
        Log::info('Starting import with ' . $rows->count() . ' rows');
        
        $successCount = 0;
        $dataToInsert = [];
        
        foreach ($rows as $index => $row) {
            try {
                // Format no_ktp
                $noKtp = "00000000";
                if (isset($row['no_ktp'])) {
                    if (is_numeric($row['no_ktp'])) {
                        $noKtp = str_pad($row['no_ktp'], 8, "0", STR_PAD_LEFT);
                    } else {
                        $noKtp = (string)$row['no_ktp'];
                    }
                }
                
                // Format phone numbers
                $noTelp = "";
                if (isset($row['no_telp'])) {
                    // Hapus karakter non-digit
                    $cleaned = preg_replace('/[^0-9]/', '', $row['no_telp']);
                    
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
                $blok = isset($row['blok']) ? (string)$row['blok'] : "N/A";
                $unit = isset($row['unit']) ? (string)$row['unit'] : "N/A";
                $email = isset($row['email']) ? (string)$row['email'] : "N/A";
                $alamat2 = isset($row['alamat_2']) ? (string)$row['alamat_2'] : "";
                
                // For debugging
                Log::debug('Processing address', [
                    'original' => $row['alamat'] ?? 'none',
                    'processed' => $alamat,
                    'row_number' => $index + 1
                ]);
                
                // Prepare data for insert
                $dataToInsert[] = [
                    'no_ktp' => $noKtp,
                    'nama' => isset($row['nama']) ? (string)$row['nama'] : '',
                    'alamat' => $alamat,
                    'blok' => $blok,
                    'unit' => $unit,
                    'no_telp' => $noTelp,
                    'email' => $email,
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
                    'row' => isset($row) ? json_encode($row) : 'No data'
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