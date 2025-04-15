<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exports\PelangganExport;
use Maatwebsite\Excel\Facades\Excel;

class PelangganExportController extends Controller
{
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
        
        // Ambil data brand untuk filter tambahan
        $brands = \App\Models\Pelanggan::select('id_brand')
                    ->distinct()
                    ->whereNotNull('id_brand')
                    ->pluck('id_brand', 'id_brand')
                    ->toArray();
        
        return view('pelanggan.export', compact('locations', 'brands'));
    }
    
    /**
     * Export pelanggan berdasarkan lokasi dan brand
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export(Request $request)
    {
        $request->validate([
            'location' => 'nullable|string',
            'brand' => 'nullable|string',
        ]);
        
        $location = $request->input('location');
        $brand = $request->input('brand');
        
        $filename = 'pelanggan';
        if ($location) {
            $filename .= "-" . str_replace(' ', '_', strtolower($location));
        }
        if ($brand) {
            $filename .= "-brand_" . str_replace(' ', '_', strtolower($brand));
        }
        $filename .= '-' . now()->format('Y-m-d') . '.xlsx';
        
        return Excel::download(new PelangganExport($location, $brand), $filename);
    }
}