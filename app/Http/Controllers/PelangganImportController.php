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
            // Perform table reset in a separate transaction
            try {
                DB::beginTransaction();
                DB::statement('SET FOREIGN_KEY_CHECKS=0');
                DB::table('pelanggan')->truncate();
                DB::statement('ALTER TABLE pelanggan AUTO_INCREMENT = 1');
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error resetting table', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return redirect()->back()->with('error', 'Gagal mereset tabel: ' . $e->getMessage());
            }
            
            // Then import data (now using WithoutTransaction)
            Excel::import(new PelangganImport, $request->file('file'));
            
            // Optional: Fix existing data in the database (if needed)
            $this->fixExistingData();
            
            return redirect()->back()->with('success', 'Data pelanggan berhasil diimpor!');
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