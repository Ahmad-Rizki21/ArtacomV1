<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exports\PelangganExport;
use Maatwebsite\Excel\Facades\Excel;

class PelangganExportController extends Controller
{
    /**
     * Export pelanggan berdasarkan lokasi
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export(Request $request)
    {
        $location = $request->input('location');
        $filename = 'pelanggan' . ($location ? "-{$location}" : '') . '.xlsx';
        
        return Excel::download(new PelangganExport($location), $filename);
    }
    
    /**
     * Tampilkan form ekspor dengan pilihan lokasi
     *
     * @return \Illuminate\View\View
     */
    public function showExportForm()
    {
        $locations = [
            'Rusun Nagrak' => 'Rusun Nagrak',
            'Rusun Pinus Elok' => 'Rusun Pinus Elok',
            'Rusun Pulogebang Tower' => 'Rusun Pulogebang Tower',
            'Rusun KM2' => 'Rusun KM2',
            'Rusun Tipar Cakung' => 'Rusun Tipar Cakung',
            'Rusun Albo' => 'Rusun Albo',
            'Perumahan Tambun' => 'Perumahan Tambun',
            'Perumahan Waringin Kurung' => 'Perumahan Waringin Kurung',
            'Perumahan Parama Serang' => 'Perumahan Parama Serang',
        ];
        
        return view('pelanggan.export', compact('locations'));
    }
}