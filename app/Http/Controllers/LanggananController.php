<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exports\LanggananExport;
use App\Exports\LanggananTemplateExport;
use App\Imports\LanggananImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class LanggananController extends Controller
{
    /**
     * Menampilkan form export Langganan
     */
    public function showExportForm()
    {
        // Brand manual yang diinginkan
        $manualBrands = [
            'Jakinet' => 'Jakinet',
            'Jelantik' => 'Jelantik',
            'Jelantik Nagrak' => 'Jelantik Nagrak'
        ];
        
        // Dapatkan brand unik dari database
        $dbBrands = DB::table('langganan')
            ->select('id_brand')
            ->whereNotNull('id_brand')
            ->distinct()
            ->get()
            ->pluck('id_brand', 'id_brand')
            ->toArray();
        
        // Gabungkan dan filter brand
        $allBrands = array_merge(
            ['all' => '-- Semua Brand --'],
            array_intersect_key($dbBrands, $manualBrands),
            $manualBrands
        );

        // Opsi status
        $statusOptions = [
            'all' => '-- Semua Status --',
            'aktif' => 'Aktif',
            'suspend' => 'Suspend'
        ];

        return view('langganan.export', [
            'brands' => $allBrands,
            'statusOptions' => $statusOptions,
            'pageTitle' => 'Export Data Langganan'
        ]);
    }

    /**
     * Proses export Langganan
     */
    public function export(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'brand' => 'nullable|string',
            'status' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Ambil parameter
        $brand = $request->input('brand', 'all');
        $status = $request->input('status', 'all');
        
        // Buat nama file
        $filename = 'langganan';
        if ($brand !== 'all') $filename .= "-{$brand}";
        if ($status !== 'all') $filename .= "-{$status}";
        $filename .= '.xlsx';

        // Lakukan export
        try {
            return Excel::download(
                new LanggananExport($brand, $status), 
                $filename
            );
        } catch (\Exception $e) {
            // Catat kesalahan
            Log::error('Export Langganan Gagal', [
                'message' => $e->getMessage(),
                'brand' => $brand,
                'status' => $status
            ]);
            
            return redirect()->back()
                ->with('error', 'Gagal mengekspor data. ' . $e->getMessage());
        }
    }

    /**
     * Download template impor Langganan
     */
    public function downloadTemplate()
    {
        return Excel::download(
            new LanggananTemplateExport(), 
            'template_langganan.xlsx'
        );
    }

    /**
     * Menampilkan form impor Langganan
     */
    public function showImportForm()
    {
        return view('langganan.import', [
            'pageTitle' => 'Import Data Langganan'
        ]);
    }

    /**
     * Proses impor Langganan
     */
    public function import(Request $request)
    {
        // Validasi file
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240' // Maks 10MB
        ]);

        try {
            // Proses impor
            $import = new LanggananImport();
            Excel::import($import, $request->file('file'));

            // Redirect dengan pesan sukses
            return redirect()->back()
                ->with('success', 'Data Langganan berhasil diimpor.');
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            // Tangani kesalahan validasi
            $failures = $e->failures();
            
            return redirect()->back()
                ->with('import_errors', $failures);
        } catch (\Exception $e) {
            // Catat kesalahan
            Log::error('Impor Langganan Gagal', [
                'message' => $e->getMessage()
            ]);
            
            return redirect()->back()
                ->with('error', 'Gagal mengimpor data. ' . $e->getMessage());
        }
    }
}