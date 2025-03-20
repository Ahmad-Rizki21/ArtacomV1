<?php

namespace App\Imports;

use App\Models\DataTeknis;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class DataTeknisImport implements ToModel, WithHeadingRow, WithValidation, WithBatchInserts, WithChunkReading
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Jika ID sudah ada, update record yang ada
        $existingRecord = DataTeknis::where('id', $row['id'])->first();
        
        if ($existingRecord) {
            $existingRecord->update([
                'pelanggan_id'      => $row['pelanggan_id'],
                'id_vlan'           => $row['id_vlan'],
                'password_pppoe'    => $row['password_pppoe'],
                'ip_pelanggan'      => $row['ip_pelanggan'],
                'profile_pppoe'     => $row['profile_pppoe'],
                'olt'               => $row['olt'],
                'olt_custom'        => $row['olt_custom'] ?? null,
                'pon'               => $row['pon'],
                'otb'               => $row['otb'],
                'odc'               => $row['odc'],
                'odp'               => $row['odp'],
                'onu_power'         => $row['onu_power'],
                'created_at'        => $row['created_at'] ?? now(),
                'updated_at'        => now()
            ]);
            return null;
        }
        
        // Jika ID tidak ada, buat record baru
        return new DataTeknis([
            'id'                => $row['id'],
            'pelanggan_id'      => $row['pelanggan_id'],
            'id_vlan'           => $row['id_vlan'],
            'password_pppoe'    => $row['password_pppoe'],
            'ip_pelanggan'      => $row['ip_pelanggan'],
            'profile_pppoe'     => $row['profile_pppoe'],
            'olt'               => $row['olt'],
            'olt_custom'        => $row['olt_custom'] ?? null,
            'pon'               => $row['pon'],
            'otb'               => $row['otb'],
            'odc'               => $row['odc'],
            'odp'               => $row['odp'],
            'onu_power'         => $row['onu_power'],
            'created_at'        => $row['created_at'] ?? now(),
            'updated_at'        => $row['updated_at'] ?? now()
        ]);
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'id'                => 'nullable|integer',
            'pelanggan_id'      => 'required|integer|exists:pelanggan,id',
            'id_vlan'           => 'required|string|max:191',
            'password_pppoe'    => 'required|string|max:191',
            'ip_pelanggan'      => 'required|ip',
            'profile_pppoe'     => 'required|string|max:191',
            'olt'               => 'required|string|max:191',
            'olt_custom'        => 'nullable|string|max:191',
            'pon'               => 'required|integer',
            'otb'               => 'required|integer',
            'odc'               => 'required|integer',
            'odp'               => 'required|integer',
            'onu_power'         => 'required|integer',
            'created_at'        => 'nullable|date',
            'updated_at'        => 'nullable|date'
        ];
    }

    /**
     * @return int
     */
    public function batchSize(): int
    {
        return 100; // Jumlah baris yang diinsert per batch
    }

    /**
     * @return int
     */
    public function chunkSize(): int
    {
        return 100; // Jumlah baris yang dibaca per chunk
    }
}