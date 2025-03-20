<?php

namespace App\Imports;

use App\Models\Langganan;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Carbon\Carbon;

class LanggananImport implements ToModel, WithHeadingRow, WithValidation, WithBatchInserts, WithChunkReading
{
    // Mapping brand
    private $brandMapping = [
        'ajn-01' => 'Jakinet',
        'ajn-02' => 'Jelantik',
        'ajn-03' => 'Jelantik Nagrak'
    ];

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Normalisasi brand
        $brand = $this->brandMapping[$row['brand']] ?? $row['brand'];
        
        // Normalisasi data
        $metodePembayaran = strtolower($row['metode_pembayaran'] ?? 'otomatis');
        $userStatus = strtolower($row['user_status'] ?? 'aktif');

        // Cari atau buat record berdasarkan pelanggan_id
        $langganan = Langganan::updateOrCreate(
            [
                'pelanggan_id' => $row['id_pelanggan']
            ],
            [
                'id_pelanggan' => $row['id_pelanggan'] ?? null,
                'profile_pppoe' => $row['profile_pppoe'] ?? null,
                'olt' => $row['olt'] ?? null,
                'id_brand' => $brand,
                'layanan' => $row['layanan'] ?? null,
                'total_harga_layanan_x_pajak' => $row['total_harga_layanan_x_pajak'] ?? 0,
                'tgl_jatuh_tempo' => !empty($row['tgl_jatuh_tempo']) 
                    ? Carbon::parse($row['tgl_jatuh_tempo'])->format('Y-m-d') 
                    : null,
                'metode_pembayaran' => in_array($metodePembayaran, ['otomatis', 'manual']) 
                    ? $metodePembayaran 
                    : 'otomatis',
                'user_status' => in_array($userStatus, ['aktif', 'suspend']) 
                    ? $userStatus 
                    : 'aktif',
                'tgl_invoice_terakhir' => !empty($row['tgl_invoice_terakhir']) 
                    ? Carbon::parse($row['tgl_invoice_terakhir'])->format('Y-m-d') 
                    : null,
            ]
        );

        return $langganan;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'id_pelanggan' => 'required|exists:pelanggan,id',
            'brand' => 'required|string|max:191',
            'layanan' => 'required|string|max:191',
            'profile_pppoe' => 'nullable|string|max:191',
            'olt' => 'nullable|string|max:191',
            'total_harga_layanan_x_pajak' => 'required|numeric|min:0',
            'tgl_jatuh_tempo' => 'nullable|date',
            'metode_pembayaran' => 'nullable|in:otomatis,manual',
            'user_status' => 'nullable|in:aktif,suspend',
            'tgl_invoice_terakhir' => 'nullable|date'
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