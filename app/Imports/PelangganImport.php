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
                // Format no_ktp - pad with zeros if numeric
                $noKtp = "00000000";
                if (isset($row['no_ktp'])) {
                    if (is_numeric($row['no_ktp'])) {
                        $noKtp = str_pad($row['no_ktp'], 8, "0", STR_PAD_LEFT);
                    } else {
                        $noKtp = (string)$row['no_ktp'];
                    }
                }
                
                // Format phone numbers correctly and preserve N/A
                $noTelp = "";
                if (isset($row['no_telp'])) {
                    if (is_numeric($row['no_telp'])) {
                        // Convert scientific notation to regular number
                        $noTelp = sprintf("%.0f", (float)$row['no_telp']);
                    } else {
                        $noTelp = (string)$row['no_telp']; // Keep N/A as is
                    }
                }
                
                // Format alamat properly - FIXED to remove "n" between Perumahan and location
                $alamat = "";
                if (isset($row['alamat'])) {
                    if (stripos($row['alamat'], 'Perumaha') === 0) {
                        // Fix truncated "Perumahan" text
                        if (stripos($row['alamat'], 'PerumahaC') === 0) {
                            $alamat = "Perumahan Tambun"; // Remove "n"
                        } else if (stripos($row['alamat'], 'PerumahaN/A') === 0) {
                            $alamat = "Perumahan Tambun"; // Remove "n"
                        } else if (stripos($row['alamat'], 'PerumahaB') === 0) {
                            $alamat = "Perumahan Waringin"; // Remove "n"
                        } else if (stripos($row['alamat'], 'PerumahaA') === 0) {
                            $alamat = "Perumahan Waringin"; // Remove "n"
                        } else {
                            $alamat = str_replace("Perumaha", "Perumahan ", $row['alamat']);
                        }
                    } else {
                        $alamat = (string)$row['alamat'];
                    }
                }
                
                // Process other fields (keeping N/A when present)
                $blok = isset($row['blok']) ? (string)$row['blok'] : "N/A";
                $unit = isset($row['unit']) ? (string)$row['unit'] : "N/A";
                $email = isset($row['email']) ? (string)$row['email'] : "N/A";
                $alamat2 = isset($row['alamat_2']) ? (string)$row['alamat_2'] : "";
                
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
                
                // Insert in batches of 100 records
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
        
        Log::info('Import completed', [
            'total' => $rows->count(),
            'success' => $successCount,
            'failed' => $rows->count() - $successCount
        ]);
    }
}