<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class PelangganTemplateExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithColumnFormatting
{
    /**
     * @return array
     */
    public function array(): array
    {
        return [
            ['00000000', 'Fajar', 'Perumahan Tambun', 'C', '21', '087840921202', 'fajar@gmail.com', 'Jl. Kamboja 2 blok C 20A nomor 21, Tambun Selatan'],
            ['00000000', 'M. Yusuf', 'Perumahan Tambun', 'N/A', '109', '089643691484', 'Aprilia.pransiska@gmail.com', 'Jl. Mangga 1 RT 05/06 no 109 Tridaya Sakti, Tambun Selatan'],
            ['00000000', 'Maman', 'Perumahan Waringin', 'B6', '2', '085212388332', 'maman123@gmail.com', 'Perumahan Waringin Blok B6 No 2'],
            ['00000000', 'Slamet', 'Perumahan Waringin', 'B5', '16', '085878156132', 'slamet12@gmail.com', 'Perumahan Waringin Blok B5 No 16']
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
        // Style for header row
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);
        $sheet->getStyle('A1:H1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('D3D3D3');
        
        // Add notes row at the bottom
        $lastRow = count($this->array()) + 3;
        $sheet->setCellValue('A' . $lastRow, 'Catatan Penting:');
        $sheet->getStyle('A' . $lastRow)->getFont()->setBold(true);
        
        $lastRow++;
        $sheet->setCellValue('A' . $lastRow, '1. Format nomor KTP: diisi dengan nomor KTP (atau 00000000 jika tidak ada)');
        
        $lastRow++;
        $sheet->setCellValue('A' . $lastRow, '2. Alamat: untuk perumahan, gunakan format "Perumahan [Nama]" (mis. "Perumahan Tambun", "Perumahan Waringin")');
        
        $lastRow++;
        $sheet->setCellValue('A' . $lastRow, '3. Blok: gunakan "N/A" jika tidak ada');
        
        $lastRow++;
        $sheet->setCellValue('A' . $lastRow, '4. Nomor telepon: masukkan nomor tanpa kode negara');
        
        // Merge cells for notes
        $sheet->mergeCells('A' . ($lastRow-3) . ':H' . ($lastRow-3));
        $sheet->mergeCells('A' . ($lastRow-2) . ':H' . ($lastRow-2));
        $sheet->mergeCells('A' . ($lastRow-1) . ':H' . ($lastRow-1));
        $sheet->mergeCells('A' . $lastRow . ':H' . $lastRow);
        
        // Style for the example data
        $sheet->getStyle('A2:H' . (count($this->array()) + 1))->getFont()->setItalic(true);
        
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
            'A' => NumberFormat::FORMAT_TEXT, // no_ktp as text
            'F' => NumberFormat::FORMAT_TEXT, // no_telp as text
        ];
    }
}