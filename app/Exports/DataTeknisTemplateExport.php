<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class DataTeknisTemplateExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithColumnFormatting
{
    /**
     * @return array
     */
    public function array(): array
    {
        return [
            // Contoh data yang sesuai dengan screenshot pertama
            [1, 1, 100, 'TMB-KBJ2-C20A-Fajar', 'support123.!!', '192.168.30.5', '10Mbps-a', 'Tambun', 'N/A', 0, 0, 0, 0, 0, '', ''],
            [2, 2, 100, 'TMB-MGG-109-Yusuf', 'support123.!!', '192.168.30.6', '10Mbps-a', 'Tambun', 'N/A', 0, 0, 0, 0, 0, '', ''],
            [3, 3, 100, 'TMB-KHM-42-Delif', 'support123.!!', '192.168.30.7', '10Mbps-a', 'Tambun', 'N/A', 0, 0, 0, 0, 0, '', ''],
            // Contoh untuk lokasi berbeda
            [4, 4, 10, 'WRG-A3-9-Dewi', 'support123.!!', '192.168.40.9', '20Mbps-a', 'Parama', 'N/A', 0, 0, 0, 0, 0, '', ''],
            [5, 5, 10, 'WRG-A1-7-Adien', 'support123.!!', '192.168.40.10', '20Mbps-b', 'Parama', 'N/A', 0, 0, 0, 0, 0, '', '']
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
            'id_pelanggan', // Menambahkan kolom ini yang hilang
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
        // Styling header dan baris contoh
        $sheet->getStyle('A1:P1')->getFont()->setBold(true);
        $sheet->getStyle('A1:P1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('D3D3D3');
        
        // Menambahkan petunjuk pengisian
        $lastRow = count($this->array()) + 3;
        $sheet->setCellValue('A' . $lastRow, 'Petunjuk Pengisian:');
        $sheet->getStyle('A' . $lastRow)->getFont()->setBold(true);
        
        $lastRow++;
        $sheet->setCellValue('A' . $lastRow, '1. Kolom id bisa dikosongkan untuk data baru, sistem akan mengisi otomatis');
        
        $lastRow++;
        $sheet->setCellValue('A' . $lastRow, '2. pelanggan_id harus merujuk ke ID yang sudah ada di database pelanggan');
        
        $lastRow++;
        $sheet->setCellValue('A' . $lastRow, '3. id_pelanggan adalah identifikasi unik untuk data teknis (format: LOC-AREA-NUM-NAME)');
        
        $lastRow++;
        $sheet->setCellValue('A' . $lastRow, '4. profile_pppoe diisi dengan format: [kecepatan]Mbps-[varian], contoh: 10Mbps-a');
        
        $lastRow++;
        $sheet->setCellValue('A' . $lastRow, '5. Kolom pon, otb, odc, odp, dan onu_power diisi angka (0 jika tidak ada data)');
        
        $lastRow++;
        $sheet->setCellValue('A' . $lastRow, '6. created_at dan updated_at bisa dikosongkan, sistem akan mengisi otomatis');
        
        // Memperluas area untuk petunjuk
        $sheet->getStyle('A' . ($lastRow-5) . ':F' . $lastRow)->getAlignment()->setWrapText(true);
        
        return [
            1 => ['font' => ['bold' => true], 
                 'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 
                            'startColor' => ['rgb' => 'D3D3D3']]],
        ];
    }
    
    /**
     * @return array
     */
    public function columnFormats(): array
    {
        return [
            'E' => NumberFormat::FORMAT_TEXT, // password_pppoe
            'F' => NumberFormat::FORMAT_TEXT, // ip_pelanggan
            'J' => NumberFormat::FORMAT_NUMBER, // pon
            'K' => NumberFormat::FORMAT_NUMBER, // otb
            'L' => NumberFormat::FORMAT_NUMBER, // odc
            'M' => NumberFormat::FORMAT_NUMBER, // odp
            'N' => NumberFormat::FORMAT_NUMBER, // onu_power
            'O' => NumberFormat::FORMAT_DATE_DATETIME, // created_at
            'P' => NumberFormat::FORMAT_DATE_DATETIME, // updated_at
        ];
    }
}