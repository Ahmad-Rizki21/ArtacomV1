<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Imports\PelangganImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PelangganImportController extends Controller
{
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:csv,xls,xlsx'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            // Log file info untuk debugging
            $file = $request->file('file');
            Log::info('Import file details', [
                'original_name' => $file->getClientOriginalName(),
                'extension' => $file->getClientOriginalExtension(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize()
            ]);
            
            // Preview file content untuk debugging (hanya beberapa baris pertama)
            $content = file_get_contents($file->getRealPath());
            Log::info('File content preview:', [
                'first_500_chars' => substr($content, 0, 500)
            ]);
            
            // Perform table reset in a separate transaction
            try {
                DB::beginTransaction();
                DB::statement('SET FOREIGN_KEY_CHECKS=0');
                DB::table('pelanggan')->truncate();
                DB::statement('ALTER TABLE pelanggan AUTO_INCREMENT = 1');
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
                DB::commit();
                Log::info('Table truncated successfully');
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error resetting table', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return redirect()->back()->with('error', 'Gagal mereset tabel: ' . $e->getMessage());
            }
            
            // Then import data
            Excel::import(new PelangganImport, $request->file('file'));
            
            // Optional: Fix existing data in the database (if needed)
            $this->fixExistingData();
            
            // Verify import success
            $count = DB::table('pelanggan')->count();
            Log::info('Data imported successfully: ' . $count . ' records');
            
            return redirect()->back()->with('success', 'Data pelanggan berhasil diimpor! Total data: ' . $count);
        } catch (\Exception $e) {
            Log::error('Import error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Fix existing data with incorrect addresses
     */
    private function fixExistingData()
    {
        try {
            // Fix the "Perumahan n" issue in existing data
            DB::table('pelanggan')
                ->where('alamat', 'like', 'Perumahan n %')
                ->update([
                    'alamat' => DB::raw("REPLACE(alamat, 'Perumahan n ', 'Perumahan ')")
                ]);
                
            Log::info('Fixed existing data with incorrect addresses');
        } catch (\Exception $e) {
            Log::error('Failed to fix existing data', [
                'message' => $e->getMessage()
            ]);
        }
    }
}