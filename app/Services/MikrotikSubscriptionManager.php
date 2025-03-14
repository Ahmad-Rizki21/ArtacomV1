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
                // Ubah ke profile suspended
                $result = $this->mikrotikService->updatePppoeProfile(
                    $dataTeknis->id_pelanggan, 
                    'SUSPENDED'
                );

                // Nonaktifkan secret
                $this->mikrotikService->disablePppoeSecret($dataTeknis->id_pelanggan);

                Log::info('Proses suspend selesai', [
                    'id_pelanggan' => $dataTeknis->id_pelanggan,
                    'result' => $result
                ]);

                return $result;
            } else {
                // Aktifkan dengan profile asli
                $originalProfile = $this->determineProfileFromSpeed(
                    $langganan->layanan, 
                    $this->extractProfileSuffix($dataTeknis->profile_pppoe)
                );

                $result = $this->mikrotikService->updatePppoeProfile(
                    $dataTeknis->id_pelanggan, 
                    $originalProfile
                );

                // Aktifkan secret
                $this->mikrotikService->enablePppoeSecret($dataTeknis->id_pelanggan);

                Log::info('Proses aktivasi selesai', [
                    'id_pelanggan' => $dataTeknis->id_pelanggan,
                    'original_profile' => $originalProfile,
                    'result' => $result
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
     * Ekstrak suffix dari profile (misal dari 20Mbps-u, ambil 'u')
     */
    protected function extractProfileSuffix(string $profile): string
    {
        if (preg_match('/\d+Mbps-([a-z])/i', $profile, $matches)) {
            return strtolower($matches[1]);
        }
        return 'u'; // default suffix
    }

    /**
     * Tentukan profile berdasarkan kecepatan layanan
     */
    protected function determineProfileFromSpeed(string $layanan, string $suffix = 'u'): string
    {
        // Pastikan suffix valid
        $suffix = strtoupper(substr($suffix, 0, 1));
        if (!preg_match('/[A-Z]/', $suffix)) {
            $suffix = 'U';
        }

        return match ($layanan) {
            '10 Mbps' => "10Mbps-{$suffix}",
            '20 Mbps' => "20Mbps-{$suffix}",
            '30 Mbps' => "30Mbps-{$suffix}",
            '50 Mbps' => "50Mbps-{$suffix}",
            default => "DEFAULT-{$suffix}"
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
            
        foreach ($pastDueSubscriptions as $langganan) {
            // Update status di database
            $langganan->user_status = 'Suspend';
            $langganan->save();
            
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