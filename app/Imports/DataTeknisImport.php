<?php

namespace App\Imports;

use App\Models\DataTeknis;
use App\Models\Pelanggan;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class DataTeknisImport implements ToCollection, WithHeadingRow
{
    /**
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
        Log::info('Starting data teknis import with ' . $rows->count() . ' rows');
        
        $successCount = 0;
        $failedCount = 0;
        
        // Deactivate foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        try {
            // Process each row individually to avoid batch failures
            foreach ($rows as $index => $row) {
                try {
                    // Log raw data for debugging
                    Log::debug('Data teknis row ' . ($index + 2), (array)$row);
                    
                    // Skip if row is empty
                    if (empty($row) || count(array_filter((array)$row)) === 0) {
                        Log::info('Skipping empty row ' . ($index + 2));
                        continue;
                    }
                    
                    // Extract and convert data
                    $excelId = isset($row['id']) ? (int)$row['id'] : null;
                    $pelangganId = isset($row['pelanggan_id']) ? (int)$row['pelanggan_id'] : null;
                    
                    // Skip if no pelanggan_id
                    if (!$pelangganId) {
                        Log::warning('Skipping row ' . ($index + 2) . ': No pelanggan_id found');
                        $failedCount++;
                        continue;
                    }
                    
                    // Check if pelanggan exists
                    $pelangganExists = DB::table('pelanggan')->where('id', $pelangganId)->exists();
                    if (!$pelangganExists) {
                        // Create minimal pelanggan record if needed
                        if (isset($row['id_pelanggan']) && !empty($row['id_pelanggan'])) {
                            $nama = (string)$row['id_pelanggan'];
                            Log::warning('Pelanggan ID ' . $pelangganId . ' not found. Creating minimal record with name: ' . $nama);
                            
                            DB::table('pelanggan')->insert([
                                'id' => $pelangganId,
                                'nama' => $nama,
                                'no_ktp' => '0000000000000000',
                                'alamat' => 'Generated',
                                'blok' => 'N/A',
                                'unit' => 'N/A',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        } else {
                            Log::error('Skipping row ' . ($index + 2) . ': Pelanggan ID ' . $pelangganId . ' not found and cannot create without id_pelanggan');
                            $failedCount++;
                            continue;
                        }
                    }
                    
                    // Prepare data
                    $data = [
                        'pelanggan_id' => $pelangganId,
                        'id_vlan' => isset($row['id_vlan']) ? (string)$row['id_vlan'] : '',
                        'id_pelanggan' => isset($row['id_pelanggan']) ? (string)$row['id_pelanggan'] : '',
                        'password_pppoe' => isset($row['password_pppoe']) ? (string)$row['password_pppoe'] : 'support123.!!',
                        'ip_pelanggan' => isset($row['ip_pelanggan']) ? (string)$row['ip_pelanggan'] : '192.168.0.' . $pelangganId,
                        'profile_pppoe' => isset($row['profile_pppoe']) ? (string)$row['profile_pppoe'] : '10Mbps-a',
                        'olt' => isset($row['olt']) ? (string)$row['olt'] : 'Default',
                        'olt_custom' => isset($row['olt_custom']) ? (string)$row['olt_custom'] : 'N/A',
                        'pon' => isset($row['pon']) ? (int)$row['pon'] : 0,
                        'otb' => isset($row['otb']) ? (int)$row['otb'] : 0,
                        'odc' => isset($row['odc']) ? (int)$row['odc'] : 0,
                        'odp' => isset($row['odp']) ? (int)$row['odp'] : 0,
                        'onu_power' => isset($row['onu_power']) ? (int)$row['onu_power'] : 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    
                    // Check if record already exists
                    $existingRecord = DB::table('data_teknis')->where('pelanggan_id', $pelangganId)->first();
                    
                    if ($existingRecord) {
                        // Update existing record
                        DB::table('data_teknis')
                            ->where('id', $existingRecord->id)
                            ->update($data);
                        
                        Log::info('Updated data teknis for pelanggan ID ' . $pelangganId);
                    } else {
                        // Insert new record with explicit ID if provided
                        if ($excelId) {
                            $data['id'] = $excelId;
                        }
                        
                        DB::table('data_teknis')->insert($data);
                        
                        Log::info('Inserted new data teknis for pelanggan ID ' . $pelangganId . 
                                 ($excelId ? ' with ID ' . $excelId : ''));
                    }
                    
                    $successCount++;
                    
                } catch (\Exception $e) {
                    // Log error but continue with next row
                    Log::error('Error processing row ' . ($index + 2), [
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
        
        Log::info('Data teknis import completed', [
            'total' => $rows->count(),
            'success' => $successCount,
            'failed' => $failedCount
        ]);
    }
}