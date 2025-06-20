<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exports\DataTeknisExport;
use App\Exports\DataTeknisTemplateExport;
use App\Imports\DataTeknisImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class DataTeknisController extends Controller
{
    /**
     * Menampilkan form ekspor Data Teknis
     */
    public function showExportForm()
    {
        return view('data-teknis.export', [
            'pageTitle' => 'Ekspor Data Teknis'
        ]);
    }
    

    /**
     * Proses ekspor Data Teknis
     */
    public function export(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'filter_id' => 'nullable|exists:pelanggan,id'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Nama file ekspor
        $filterId = $request->input('filter_id');
        $filename = $filterId 
            ? "data_teknis_pelanggan_{$filterId}.xlsx" 
            : 'data_teknis_semua.xlsx';

        // Lakukan ekspor
        try {
            return Excel::download(
                new DataTeknisExport($filterId), 
                $filename
            );
        } catch (\Exception $e) {
            // Catat kesalahan
            Log::error('Ekspor Data Teknis Gagal: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Gagal mengekspor data. Silakan coba lagi.');
        }
    }

    /**
     * Download template impor Data Teknis
     */
    public function downloadTemplate()
    {
        return Excel::download(
            new DataTeknisTemplateExport(), 
            'template_data_teknis.xlsx'
        );
    }

    /**
     * Menampilkan form impor Data Teknis
     */
    public function showImportForm()
    {
        return view('data-teknis.import', [
            'pageTitle' => 'Impor Data Teknis'
        ]);
    }

    /**
     * Proses impor Data Teknis
     */
    public function import(Request $request)
    {
        // Validasi file
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240' // Maks 10MB
        ]);

        try {
            // Proses impor
            $import = new DataTeknisImport();
            Excel::import($import, $request->file('file'));

            // Redirect dengan pesan sukses
            return redirect()->back()
                ->with('success', 'Data Teknis berhasil diimpor.');
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            // Tangani kesalahan validasi
            $failures = $e->failures();
            
            return redirect()->back()
                ->with('import_errors', $failures);
        } catch (\Exception $e) {
            // Catat kesalahan
            Log::error('Impor Data Teknis Gagal: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Gagal mengimpor data. ' . $e->getMessage());
        }
    }
}