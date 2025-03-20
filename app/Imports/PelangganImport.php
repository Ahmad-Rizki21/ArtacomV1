<?php

namespace App\Imports;

use App\Models\Pelanggan;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class PelangganImport implements ToModel, WithHeadingRow, WithValidation, WithBatchInserts, WithChunkReading
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        return new Pelanggan([
            'no_ktp'     => $row['no_ktp'],
            'nama'       => $row['nama'],
            'alamat'     => $row['alamat'],
            'blok'       => $row['blok'] ?? null,
            'unit'       => $row['unit'] ?? null,
            'no_telp'    => $row['no_telp'],
            'email'      => $row['email'],
            'alamat_2'   => $row['alamat_2'] ?? null,
        ]);
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'no_ktp' => 'required|string|max:191',
            'nama' => 'required|string|max:191',
            'alamat' => 'required|string|max:191',
            'blok' => 'nullable|string|max:191',
            'unit' => 'nullable|string|max:191',
            'no_telp' => 'required|string|max:191',
            'email' => 'required|email|max:191',
            'alamat_2' => 'nullable|string',
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