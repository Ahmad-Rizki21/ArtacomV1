<?php

namespace App\Services;

use App\Models\Langganan;
use App\Models\MikrotikServer;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MikrotikSubscriptionManager
{
    protected $mikrotikService;

    public function __construct(MikrotikConnectionService $mikrotikService)
    {
        $this->mikrotikService = $mikrotikService;
    }

    /**
     * Handle subscription status updates based on payment status
     * 
     * @param Langganan $langganan
     * @param string $action (suspend|activate)
     * @return bool
     */
    public function handleSubscriptionStatus(Langganan $langganan, string $action = 'auto')
    {
        // Ambil informasi dari data teknis pelanggan
        $dataTeknis = optional($langganan->pelanggan)->dataTeknis;
        
        if (!$dataTeknis || !$dataTeknis->id_pelanggan) {
            Log::error('Tidak dapat menemukan data teknis atau ID Pelanggan', [
                'pelanggan_id' => $langganan->pelanggan_id,
                'data_teknis' => $dataTeknis ? 'Ada' : 'Kosong'
            ]);
            return false;
        }

        // Tentukan aksi berdasarkan status
        if ($action === 'auto') {
            $action = ($langganan->user_status === 'Aktif') ? 'activate' : 'suspend';
        }

        Log::info('Proses update status Mikrotik', [
            'pelanggan_id' => $langganan->pelanggan_id,
            'id_pelanggan' => $dataTeknis->id_pelanggan,
            'action' => $action,
            'current_profile' => $dataTeknis->profile_pppoe,
            'layanan' => $langganan->layanan
        ]);

        try {
            if ($action === 'suspend') {
                // Ubah ke profile suspended dan nonaktifkan secret
                $result = $this->mikrotikService->disablePppoeSecret($dataTeknis->id_pelanggan);
                
                // Pastikan juga profile diubah ke SUSPENDED
                $profileResult = $this->mikrotikService->updatePppoeProfile(
                    $dataTeknis->id_pelanggan, 
                    'SUSPENDED'
                );

                Log::info('Proses suspend selesai', [
                    'id_pelanggan' => $dataTeknis->id_pelanggan,
                    'result' => $result && $profileResult
                ]);

                return $result;
            } else {
                // Tentukan profile yang akan digunakan
                $originalProfile = $this->determineActiveProfile($dataTeknis, $langganan);
                
                Log::info('Mengaktifkan dengan profile', [
                    'id_pelanggan' => $dataTeknis->id_pelanggan,
                    'original_profile' => $originalProfile,
                    'data_teknis_profile' => $dataTeknis->profile_pppoe,
                    'layanan' => $langganan->layanan
                ]);

                // Aktifkan secret dengan mengembalikan ke profile asli
                $result = $this->mikrotikService->enablePppoeSecret(
                    $dataTeknis->id_pelanggan, 
                    $originalProfile
                );
                
                // Pastikan profile juga diupdate (safety check)
                $profileResult = $this->mikrotikService->updatePppoeProfile(
                    $dataTeknis->id_pelanggan, 
                    $originalProfile
                );

                Log::info('Proses aktivasi selesai', [
                    'id_pelanggan' => $dataTeknis->id_pelanggan,
                    'profile' => $originalProfile,
                    'result' => $result && $profileResult
                ]);

                return $result;
            }
        } catch (\Exception $e) {
            Log::error('Gagal update status Mikrotik', [
                'id_pelanggan' => $dataTeknis->id_pelanggan,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return false;
        }
    }

    /**
     * Tentukan profile aktif yang seharusnya digunakan
     */
    protected function determineActiveProfile($dataTeknis, $langganan)
    {
        // Ambil profile dari data teknis jika tersedia dan bukan SUSPENDED
        $profile = $dataTeknis->profile_pppoe;
        
        // Jika profile kosong atau SUSPENDED, tentukan berdasarkan layanan
        if (empty($profile) || strtoupper($profile) === 'SUSPENDED') {
            // Tentukan dari layanan
            $matches = [];
            if (preg_match('/(\d+)\s*Mbps/i', $langganan->layanan, $matches)) {
                $speed = $matches[1];
                $profile = "{$speed}Mbps-a"; // Default suffix a
            } else {
                // Fallback ke harga jika layanan tidak jelas
                $harga = $langganan->total_harga_layanan_x_pajak;
                
                if ($harga <= 150000) {
                    $profile = "10Mbps-a";
                } elseif ($harga <= 200000) {
                    $profile = "20Mbps-a";
                } elseif ($harga <= 250000) {
                    $profile = "30Mbps-a";
                } else {
                    $profile = "50Mbps-a";
                }
            }
            
            Log::info('Profile ditentukan dari layanan/harga', [
                'layanan' => $langganan->layanan,
                'harga' => $langganan->total_harga_layanan_x_pajak,
                'determined_profile' => $profile
            ]);
        } else {
            Log::info('Menggunakan profile dari data teknis', [
                'profile' => $profile
            ]);
        }
        
        return $profile;
    }

    /**
     * Ekstrak suffix dari profile (misal dari 20Mbps-u, ambil 'u')
     */
    protected function extractProfileSuffix(string $profile): string
    {
        if (preg_match('/\d+Mbps-([a-z])/i', $profile, $matches)) {
            return strtolower($matches[1]);
        }
        return 'a'; // default suffix diubah ke 'a' sesuai standard
    }

    /**
     * Tentukan profile berdasarkan kecepatan layanan
     */
    protected function determineProfileFromSpeed(string $layanan, string $suffix = 'a'): string
    {
        // Pastikan suffix valid
        $suffix = strtolower(substr($suffix, 0, 1));
        if (!preg_match('/[a-z]/', $suffix)) {
            $suffix = 'a';
        }

        return match ($layanan) {
            '10 Mbps' => "10Mbps-{$suffix}",
            '20 Mbps' => "20Mbps-{$suffix}",
            '30 Mbps' => "30Mbps-{$suffix}",
            '50 Mbps' => "50Mbps-{$suffix}",
            default => "20Mbps-{$suffix}" // Default ke 20Mbps
        };
    }

    /**
     * Proses langganan yang sudah melewati tanggal jatuh tempo
     */
    public function processPastDueSubscriptions()
    {
        $now = Carbon::now();
        $count = 0;
        
        // Cari langganan yang sudah lewat tanggal jatuh tempo
        $pastDueSubscriptions = Langganan::where('tgl_jatuh_tempo', '<', $now->format('Y-m-d'))
            ->where('user_status', 'Aktif')
            ->get();
        
        Log::info('Checking past due subscriptions', [
            'current_date' => $now->format('Y-m-d'),
            'found_subscriptions' => $pastDueSubscriptions->count()
        ]);
            
        foreach ($pastDueSubscriptions as $langganan) {
            // Update status di database
            $langganan->user_status = 'Suspend';
            $langganan->save();
            
            Log::info('Suspending past due subscription', [
                'pelanggan_id' => $langganan->pelanggan_id,
                'id_pelanggan' => $langganan->id_pelanggan,
                'tgl_jatuh_tempo' => $langganan->tgl_jatuh_tempo
            ]);
            
            // Update di Mikrotik
            if ($this->handleSubscriptionStatus($langganan, 'suspend')) {
                $count++;
            }
        }
        
        Log::info('Langganan yang sudah jatuh tempo diproses', [
            'total' => $pastDueSubscriptions->count(),
            'suspended' => $count
        ]);
        
        return $count;
    }
    
    /**
     * Sinkronisasi status di Mikrotik
     */
    public function syncMikrotikStatus(Langganan $langganan)
    {
        $idPelanggan = optional($langganan->pelanggan->dataTeknis)->id_pelanggan;
        
        if (empty($idPelanggan)) {
            Log::warning('Tidak dapat sinkronisasi status Mikrotik: ID Pelanggan kosong', [
                'pelanggan_id' => $langganan->pelanggan_id
            ]);
            return false;
        }
        
        Log::info('Sinkronisasi status Mikrotik', [
            'pelanggan_id' => $langganan->pelanggan_id,
            'id_pelanggan' => $idPelanggan,
            'status' => $langganan->user_status
        ]);
        
        // Paksa update status berdasarkan database
        return $langganan->user_status === 'Aktif' 
            ? $this->handleSubscriptionStatus($langganan, 'activate')
            : $this->handleSubscriptionStatus($langganan, 'suspend');
    }
}