<?php

namespace App\Services;

use App\Models\Langganan;
use App\Models\MikrotikServer;
use App\Models\Invoice;
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
     * @param string $action (suspend|activate|auto)
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

        // Tentukan aksi berdasarkan status jika auto
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
     * Sinkronisasi status di Mikrotik
     */
    public function syncMikrotikStatus(Langganan $langganan)
    {
        $dataTeknis = optional($langganan->pelanggan)->dataTeknis;
        
        if (!$dataTeknis || empty($dataTeknis->id_pelanggan)) {
            Log::warning('Tidak dapat sinkronisasi status Mikrotik: Data teknis atau ID Pelanggan kosong', [
                'pelanggan_id' => $langganan->pelanggan_id,
                'has_data_teknis' => $dataTeknis ? 'Ya' : 'Tidak'
            ]);
            return false;
        }
        
        Log::info('Sinkronisasi status Mikrotik', [
            'pelanggan_id' => $langganan->pelanggan_id,
            'id_pelanggan' => $dataTeknis->id_pelanggan,
            'status' => $langganan->user_status
        ]);
        
        // Paksa update status berdasarkan database
        return $langganan->user_status === 'Aktif' 
            ? $this->handleSubscriptionStatus($langganan, 'activate')
            : $this->handleSubscriptionStatus($langganan, 'suspend');
    }
    
    /**
     * Proses langganan yang jatuh tempo hari ini
     * 
     * @return array Statistik hasil proses
     */
    public function processDueDateSubscriptions()
    {
        $today = Carbon::now()->format('Y-m-d');
        $stats = [
            'total' => 0,
            'suspended' => 0,
            'skipped' => 0,
            'errors' => 0
        ];
        
        Log::info('Memproses langganan jatuh tempo hari ini', [
            'date' => $today
        ]);
        
        // Ambil langganan yang jatuh tempo tepat hari ini dan masih aktif
        $dueSubscriptions = Langganan::where('tgl_jatuh_tempo', $today)
            ->where('user_status', 'Aktif')
            ->get();
        
        $stats['total'] = $dueSubscriptions->count();
        
        Log::info('Ditemukan langganan jatuh tempo hari ini', [
            'count' => $stats['total'],
            'date' => $today
        ]);
            
        foreach ($dueSubscriptions as $langganan) {
            // Cek apakah ada invoice yang belum dibayar untuk bulan ini
            $hasUnpaidInvoice = Invoice::where('pelanggan_id', $langganan->pelanggan_id)
                ->whereMonth('tgl_invoice', Carbon::now()->month)
                ->whereYear('tgl_invoice', Carbon::now()->year)
                ->where('status_invoice', 'Menunggu Pembayaran')
                ->exists();
                
            if (!$hasUnpaidInvoice) {
                $stats['skipped']++;
                Log::info('Pelanggan tidak memiliki invoice yang belum dibayar bulan ini, dilewati', [
                    'pelanggan_id' => $langganan->pelanggan_id
                ]);
                continue;
            }
            
            try {
                // Update status di database
                $langganan->user_status = 'Suspend';
                $langganan->save();
                
                Log::info('Mensuspend pelanggan jatuh tempo hari ini', [
                    'pelanggan_id' => $langganan->pelanggan_id,
                    'tgl_jatuh_tempo' => $langganan->tgl_jatuh_tempo
                ]);
                
                // Update di Mikrotik
                if ($this->handleSubscriptionStatus($langganan, 'suspend')) {
                    $stats['suspended']++;
                } else {
                    $stats['errors']++;
                }
            } catch (\Exception $e) {
                $stats['errors']++;
                Log::error('Gagal suspend pelanggan jatuh tempo', [
                    'pelanggan_id' => $langganan->pelanggan_id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        Log::info('Proses langganan jatuh tempo selesai', $stats);
        
        return $stats;
    }
}