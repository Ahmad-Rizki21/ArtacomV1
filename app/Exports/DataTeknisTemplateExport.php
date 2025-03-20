<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class DataTeknisTemplateExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize
{
    /**
     * @return array
     */
    public function array(): array
    {
        return [
            ['1', '1001', 'VLAN100', 'password123', '192.168.1.100', 'Profile1', 'OLT1', 'Custom OLT', '1', '1', '1', '1', '10', '2023-12-31 23:59:59', '2024-01-01 00:00:00'],
            ['2', '1002', 'VLAN101', 'password456', '192.168.1.101', 'Profile2', 'OLT2', 'Custom OLT 2', '2', '2', '2', '2', '15', '2024-01-01 00:00:00', '2024-01-02 00:00:00']
        ];
    }
    
    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'id',
            'pelanggan_id',
            'id_vlan',
            'password_pppoe',
            'ip_pelanggan',
            'profile_pppoe',
            'olt',
            'olt_custom',
            'pon',
            'otb',
            'odc',
            'odp',
            'onu_power',
            'created_at',
            'updated_at'
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