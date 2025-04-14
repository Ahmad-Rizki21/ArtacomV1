<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Exports\PelangganTemplateExport;
use Illuminate\Support\Facades\Response;

class TemplateController extends Controller
{
    public function downloadPelangganTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set header - PENTING: Header harus sama dengan yang diharapkan oleh import
        $headers = [
            'id',
            'no_ktp', 
            'nama', 
            'alamat', 
            'blok', 
            'unit', 
            'no_telp', 
            'email', 
            'alamat_2', 
            'tgl_instalasi',
            'id_brand',
            'layanan',
            'brand_default'
        ];
        $sheet->fromArray([$headers], NULL, 'A1');
        
        // Tambahkan contoh data
        $exampleData = [
            [
                '1',
                '3201234567890123',     // no_ktp
                'John Doe',             // nama
                'Perumahan Tambun',     // alamat
                'A',                    // blok
                '5',                    // unit
                '08123456789',          // no_telp
                'john@example.com',     // email
                'Alamat tambahan',      // alamat_2
                '2023-04-15',           // tgl_instalasi
                'ajn-01',               // id_brand
                '20 Mbps',              // layanan
                'ajn-01'                // brand_default
            ],
            [
                '2',                   // id
                '3210987654321098',     // no_ktp
                'Jane Smith',           // nama
                'Perumahan Waringin',   // alamat
                'B',                    // blok
                '10',                   // unit
                '08987654321',          // no_telp
                'jane@example.com',     // email
                '',                     // alamat_2
                '2023-05-20',           // tgl_instalasi
                'ajn-02',               // id_brand
                '30 Mbps',              // layanan
                'ajn-02'                // brand_default
            ]
        ];
        $sheet->fromArray($exampleData, NULL, 'A2');
        
        // Auto size kolom - Perbaiki range untuk semua kolom yang ada
        foreach (range('A', 'L') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        
        // Styling - Perbaiki range untuk semua kolom header
        $sheet->getStyle('A1:L1')->getFont()->setBold(true);
        $sheet->getStyle('A1:L1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('D3D3D3');
        
        // Buat writer
        $writer = new Xlsx($spreadsheet);
        
        // Simpan ke temporary file
        $filename = 'template_import_pelanggan.xlsx';
        $tempPath = storage_path('app/public/' . $filename);
        $writer->save($tempPath);
        
        return response()->download($tempPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
    
    /**
     * Generate CSV template (alternatif untuk Excel)
     */
    public function downloadPelangganTemplateCSV()
    {
        // Header kolom yang sama dengan Excel
        $headers = [
            'id',
            'no_ktp', 
            'nama', 
            'alamat', 
            'blok', 
            'unit', 
            'no_telp', 
            'email', 
            'alamat_2', 
            'tgl_instalasi',
            'id_brand',
            'layanan',
            'brand_default'
        ];
        
        // Contoh data
        $rows = [
            [
                '1',                   // id
                '3201234567890123',     // no_ktp
                'John Doe',             // nama
                'Perumahan Tambun',     // alamat
                'A',                    // blok
                '5',                    // unit
                '08123456789',          // no_telp
                'john@example.com',     // email
                'Alamat tambahan',      // alamat_2
                '2023-04-15',           // tgl_instalasi
                'ajn-01',               // id_brand
                '20 Mbps',              // layanan
                'ajn-01'                // brand_default
            ],
            [
                '2',                   // id
                '3210987654321098',     // no_ktp
                'Jane Smith',           // nama
                'Perumahan Waringin',   // alamat
                'B',                    // blok
                '10',                   // unit
                '08987654321',          // no_telp
                'jane@example.com',     // email
                '',                     // alamat_2
                '2023-05-20',           // tgl_instalasi
                'ajn-02',               // id_brand
                '30 Mbps',              // layanan
                'ajn-02'                // brand_default
            ]
        ];
        
        // Buat CSV content
        $output = fopen('php://temp', 'w');
        
        // Add UTF-8 BOM
        fputs($output, "\xEF\xBB\xBF");
        
        // Add headers dan rows
        fputcsv($output, $headers);
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        // Return sebagai download
        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="template_import_pelanggan.csv"',
        ]);
    }
}