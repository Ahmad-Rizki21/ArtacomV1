<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class PelangganTemplateExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize
{
    /**
     * @return array
     */
    public function array(): array
    {
        return [
            ['3201234567890123', 'John Doe', 'Jl. Contoh No. 123', 'A', '5', '08123456789', 'john@example.com', 'Alamat tambahan jika ada'],
            ['3210987654321098', 'Jane Smith', 'Jl. Sample No. 456', 'B', '10', '08987654321', 'jane@example.com', '']
        ];
    }
    
    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'no_ktp',
            'nama',
            'alamat',
            'blok',
            'unit',
            'no_telp',
            'email',
            'alamat_2'
        ];
    }
    
    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D3D3D3']]],
        ];
    }
}