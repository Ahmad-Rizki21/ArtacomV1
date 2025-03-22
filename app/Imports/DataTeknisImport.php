<?php

namespace App\Imports;

use App\Models\DataTeknis;
use App\Models\Pelanggan;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Facades\Log;

class DataTeknisImport implements ToModel, WithHeadingRow, WithValidation, WithBatchInserts, WithChunkReading
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        try {
            // Log data untuk debugging
            Log::info('Importing data teknis row', ['data' => $row]);
            
            // Validasi pelanggan_id
            $pelanggan = Pelanggan::find($row['pelanggan_id']);
            if (!$pelanggan) {
                Log::error('Pelanggan tidak ditemukan', ['pelanggan_id' => $row['pelanggan_id']]);
                return null;
            }
            
            // Pastikan id_vlan adalah string
            $idVlan = (string)$row['id_vlan'];
            
            // Validasi dan format id_pelanggan
            $idPelanggan = $row['id_pelanggan'];
            
            // Cari record yang sudah ada berdasarkan pelanggan_id atau id_pelanggan
            $existingRecord = DataTeknis::where(function($query) use ($row, $idPelanggan) {
                $query->where('pelanggan_id', $row['pelanggan_id'])
                      ->orWhere('id_pelanggan', $idPelanggan);
            })->first();
            
            // Pastikan pon, otb, odc, odp, dan onu_power adalah integer
            $pon = intval($row['pon'] ?? 0);
            $otb = intval($row['otb'] ?? 0);
            $odc = intval($row['odc'] ?? 0);
            $odp = intval($row['odp'] ?? 0);
            $onuPower = intval($row['onu_power'] ?? 0);
            
            // Data yang akan dimasukkan/diupdate
            $data = [
                'pelanggan_id'      => $row['pelanggan_id'],
                'id_vlan'           => $idVlan, // Pastikan string
                'id_pelanggan'      => $idPelanggan,
                'password_pppoe'    => $row['password_pppoe'],
                'ip_pelanggan'      => $row['ip_pelanggan'],
                'profile_pppoe'     => $row['profile_pppoe'],
                'olt'               => $row['olt'],
                'olt_custom'        => $row['olt_custom'] ?? 'N/A',
                'pon'               => $pon,
                'otb'               => $otb,
                'odc'               => $odc,
                'odp'               => $odp,
                'onu_power'         => $onuPower,
                'updated_at'        => now()
            ];
            
            // Jika ada created_at di data dan valid, gunakan itu
            if (isset($row['created_at']) && !empty($row['created_at'])) {
                try {
                    $data['created_at'] = \Carbon\Carbon::parse($row['created_at']);
                } catch (\Exception $e) {
                    $data['created_at'] = now();
                }
            } else if (!$existingRecord) {
                // Hanya set created_at jika ini record baru
                $data['created_at'] = now();
            }
            
            // Jika record sudah ada, update
            if ($existingRecord) {
                Log::info('Updating existing data teknis', [
                    'id' => $existingRecord->id,
                    'pelanggan_id' => $existingRecord->pelanggan_id
                ]);
                
                $existingRecord->update($data);
                return null;
            }
            
            // Jika ada ID di data import dan valid, gunakan itu
            if (isset($row['id']) && is_numeric($row['id'])) {
                $data['id'] = $row['id'];
            }
            
            // Buat record baru
            Log::info('Creating new data teknis', [
                'pelanggan_id' => $data['pelanggan_id'],
                'id_pelanggan' => $data['id_pelanggan']
            ]);
            
            return new DataTeknis($data);
            
        } catch (\Exception $e) {
            Log::error('Error importing data teknis row', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'row' => $row
            ]);
            
            // Re-throw untuk ditangani di controller
            throw $e;
        }
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'pelanggan_id'      => 'required|exists:pelanggan,id',
            'id_pelanggan'      => 'required|string|max:191',
            'id_vlan'           => 'required',  // Hapus validasi string untuk fleksibilitas
            'password_pppoe'    => 'required|string|max:191',
            'ip_pelanggan'      => 'required|string|max:191',
            'profile_pppoe'     => 'required|string|max:191',
            'olt'               => 'required|string|max:191',
            'olt_custom'        => 'nullable|string|max:191',
            'pon'               => 'nullable',
            'otb'               => 'nullable',
            'odc'               => 'nullable',
            'odp'               => 'nullable',
            'onu_power'         => 'nullable',
            'created_at'        => 'nullable',
            'updated_at'        => 'nullable'
        ];
    }

    /**
     * Customize the validation messages
     *
     * @return array
     */
    public function customValidationMessages()
    {
        return [
            'id_vlan.required' => 'Kolom id_vlan harus diisi',
            'pelanggan_id.exists' => 'Pelanggan dengan ID ini tidak ditemukan',
            'id_pelanggan.required' => 'Kolom id_pelanggan harus diisi',
        ];
    }

    /**
     * @return int
     */
    public function batchSize(): int
    {
        return 100;
    }

    /**
     * @return int
     */
    public function chunkSize(): int
    {
        return 100;
    }
}