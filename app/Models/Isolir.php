<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class Isolir extends Model
{
    use HasFactory;

    protected $table = 'isolir';

    protected $fillable = [
        'langganan_id',
        'pelanggan_id',
        'brand',
        'id_pelanggan', // PPPoE Name
        'profile_pppoe',
        'olt', // Server Mikrotik
        'alasan_isolir',
        'tanggal_isolir',
        'tanggal_aktif_kembali',
        'status_isolir', // pending, aktif, selesai
        'user_id', // User yang melakukan isolir
        'catatan'
    ];

    protected $dates = [
        'tanggal_isolir',
        'tanggal_aktif_kembali'
    ];

    // Relasi ke Langganan
    public function langganan()
    {
        return $this->belongsTo(Langganan::class, 'langganan_id');
    }

    // Relasi ke Pelanggan
    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class, 'pelanggan_id');
    }

    // Relasi ke User (opsional, jika ingin melacak siapa yang melakukan isolir)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relasi ke Mikrotik Server
    public function mikroServer()
    {
        return $this->belongsTo(MikrotikServer::class, 'olt', 'name');
    }

    // Scope untuk status isolir
    public function scopeAktif($query)
    {
        return $query->where('status_isolir', 'aktif');
    }

    // Method untuk membuat record isolir baru
    public static function buatIsolir(Langganan $langganan, $alasan = null)
    {
        try {
            return self::create([
                'langganan_id' => $langganan->getAttribute('id'),
                'pelanggan_id' => $langganan->pelanggan_id,
                'brand' => $langganan->id_brand,
                'id_pelanggan' => $langganan->id_pelanggan, // PPPoE Name
                'profile_pppoe' => $langganan->profile_pppoe,
                'olt' => $langganan->olt, // Server Mikrotik
                'alasan_isolir' => $alasan,
                'tanggal_isolir' => now(),
                'status_isolir' => 'aktif',
                'user_id' => Auth::id() // Gunakan Auth facade
            ]);
        } catch (\Exception $e) {
            Log::error('Gagal membuat record isolir', [
                'langganan_id' => $langganan->id,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    // Method untuk proses isolir otomatis
    public function prosesIsolir()
    {
        try {
            // Dapatkan server Mikrotik terkait
            $mikroServer = $this->mikroServer;

            if (!$mikroServer) {
                Log::warning('Server Mikrotik tidak ditemukan', [
                    'isolir_id' => $this->id,
                    'olt' => $this->olt
                ]);
                return false;
            }

            // Nonaktifkan PPPoE Secret
            $mikrotikService = app(\App\Services\MikrotikConnectionService::class);
            $result = $mikrotikService->disablePppoeSecret(
                $mikroServer, 
                $this->id_pelanggan // Gunakan PPPoE Name
            );

            // Update status langganan
            $langganan = $this->langganan;
            if ($langganan) {
                $langganan->update([
                    'user_status' => 'Isolir'
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Gagal proses isolir', [
                'isolir_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    // Method untuk mengaktifkan kembali
    public function aktivasiKembali()
    {
        try {
            // Dapatkan server Mikrotik terkait
            $mikroServer = $this->mikroServer;

            if (!$mikroServer) {
                Log::warning('Server Mikrotik tidak ditemukan', [
                    'isolir_id' => $this->id,
                    'olt' => $this->olt
                ]);
                return false;
            }

            // Aktifkan PPPoE Secret
            $mikrotikService = app(\App\Services\MikrotikConnectionService::class);
            $result = $mikrotikService->enablePppoeSecret(
                $mikroServer, 
                $this->id_pelanggan // Gunakan PPPoE Name
            );

            // Update status langganan
            $langganan = $this->langganan;
            if ($langganan) {
                $langganan->update([
                    'user_status' => 'Aktif',
                    'tgl_jatuh_tempo' => now()->addMonth()
                ]);
            }

            // Update status isolir
            $this->update([
                'status_isolir' => 'selesai',
                'tanggal_aktif_kembali' => now()
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error('Gagal aktivasi kembali', [
                'isolir_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
}