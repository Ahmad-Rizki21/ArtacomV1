<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pelanggan;
use Illuminate\Support\Facades\DB;

class StatusController extends Controller
{
    public function show($id)
    {
        // Ambil data pelanggan
        $pelanggan = Pelanggan::findOrFail($id);
        
        // Ambil data langganan
        $langganan = DB::table('langganan')
            ->where('pelanggan_id', $id)
            ->first();
        
        // Tentukan status
        $status = 'Aktif';
        $isSuspended = false;
        
        if ($langganan && isset($langganan->user_status)) {
            $status = $langganan->user_status;
            $isSuspended = ($langganan->user_status === 'suspended');
        }
        
        return view('admin.status', compact('pelanggan', 'status', 'isSuspended'));
    }
}