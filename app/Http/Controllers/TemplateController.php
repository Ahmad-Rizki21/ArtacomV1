<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Exports\PelangganTemplateExport;

class TemplateController extends Controller
{
    public function downloadPelangganTemplate()
    {

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        
        // Set header
        $headers = ['no_ktp', 'nama', 'alamat', 'blok', 'unit', 'no_telp', 'email', 'alamat_2'];
        $sheet->fromArray([$headers], NULL, 'A1');
        
        // Tambahkan contoh data
        $exampleData = [
            ['3201234567890123', 'John Doe', 'Jl. Contoh No. 123', 'A', '5', '08123456789', 'john@example.com', 'Alamat tambahan jika ada'],
            ['3210987654321098', 'Jane Smith', 'Jl. Sample No. 456', 'B', '10', '08987654321', 'jane@example.com', '']
        ];
        $sheet->fromArray($exampleData, NULL, 'A2');
        
        // Auto size kolom
        foreach (range('A', 'H') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        
        // Styling
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);
        $sheet->getStyle('A1:H1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('D3D3D3');
        
        // Buat writer
        $writer = new Xlsx($spreadsheet);
        
        // Simpan ke temporary file
        $filename = 'template_import_pelanggan.xlsx';
        $tempPath = storage_path('app/public/' . $filename);
        $writer->save($tempPath);

        
        
        // return response()->download($tempPath, $filename, [
        //     'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        // ])->deleteFileAfterSend(true);

        return Excel::download(new PelangganTemplateExport, 'template_import_pelanggan.xlsx');

        
    }
}