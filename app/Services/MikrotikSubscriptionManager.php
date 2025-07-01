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
    protected $pppoeSecretService;

    public function __construct(MikrotikConnectionService $mikrotikService)
    {
        $this->mikrotikService = $mikrotikService;
        $this->pppoeSecretService = new MikrotikPppoeSecretService($mikrotikService);
        
        Log::debug('MikrotikSubscriptionManager initialized');
    }

    /**
     * Membuat PPPoE Secret di Mikrotik berdasarkan data teknis
     *
     * @param \App\Models\DataTeknis $dataTeknis
     * @return bool
     */
    public function createPppoeSecretOnMikrotik($dataTeknis)
    {
        Log::debug('Memulai createPppoeSecretOnMikrotik untuk data teknis ID: ' . ($dataTeknis->id ?? 'unknown'), [
            'data_teknis' => $dataTeknis ? $dataTeknis->toArray() : null
        ]);

        if (!$dataTeknis || empty($dataTeknis->id_pelanggan)) {
            Log::error('Data teknis tidak lengkap untuk membuat PPPoE secret', [
                'data_teknis' => $dataTeknis ? $dataTeknis->toArray() : null
            ]);
            return false;
        }

        $data = [
            'name' => $dataTeknis->id_pelanggan,
            'password' => $dataTeknis->password_pppoe,
            'profile' => $dataTeknis->profile_pppoe,
            'remote-address' => $dataTeknis->ip_pelanggan,
            'service' => 'pppoe',
            'comment' => 'Auto-created by system on ' . Carbon::now()->toDateTimeString()
        ];

        Log::info('Mempersiapkan pembuatan PPPoE secret di Mikrotik', [
            'data' => $data
        ]);

        try {
            $result = $this->pppoeSecretService->createSecret($data);

            if ($result) {
                Log::info('PPPoE secret berhasil dibuat di Mikrotik', [
                    'id_pelanggan' => $dataTeknis->id_pelanggan,
                    'profile' => $dataTeknis->profile_pppoe
                ]);
            } else {
                Log::error('Gagal membuat PPPoE secret di Mikrotik', [
                    'id_pelanggan' => $dataTeknis->id_pelanggan,
                    'data' => $data
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Exception saat membuat PPPoE secret: ' . $e->getMessage(), [
                'id_pelanggan' => $dataTeknis->id_pelanggan,
                'data' => $data,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
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
        $dataTeknis = optional($langganan->pelanggan)->dataTeknis;
        
        if (!$dataTeknis || !$dataTeknis->id_pelanggan) {
            Log::error('Tidak dapat menemukan data teknis atau ID Pelanggan', [
                'pelanggan_id' => $langganan->pelanggan_id,
                'data_teknis' => $dataTeknis ? 'Ada' : 'Kosong'
            ]);
            return false;
        }

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
                $result = $this->mikrotikService->disablePppoeSecret($dataTeknis->id_pelanggan);
                $profileResult = $this->mikrotikService->updatePppoeProfile(
                    $dataTeknis->id_pelanggan, 
                    'SUSPENDED'
                );

                // Langkah 2: Hapus koneksi aktif untuk memaksa logout (KODE BARU YANG DITAMBAHKAN)
                $this->mikrotikService->removePppoeActiveConnection($dataTeknis->id_pelanggan);

                Log::info('Proses suspend selesai, termasuk force logout', [
                    'id_pelanggan' => $dataTeknis->id_pelanggan,
                    'result' => $result && $profileResult
                ]);

                Log::info('Proses suspend selesai', [
                    'id_pelanggan' => $dataTeknis->id_pelanggan,
                    'result' => $result && $profileResult
                ]);

                return $result && $profileResult;
            } else {
                $originalProfile = $this->determineActiveProfile($dataTeknis, $langganan);
                
                Log::info('Mengaktifkan dengan profile', [
                    'id_pelanggan' => $dataTeknis->id_pelanggan,
                    'original_profile' => $originalProfile,
                    'data_teknis_profile' => $dataTeknis->profile_pppoe,
                    'layanan' => $langganan->layanan
                ]);

                $result = $this->mikrotikService->enablePppoeSecret(
                    $dataTeknis->id_pelanggan, 
                    $originalProfile
                );
                $profileResult = $this->mikrotikService->updatePppoeProfile(
                    $dataTeknis->id_pelanggan, 
                    $originalProfile
                );

                Log::info('Proses aktivasi selesai', [
                    'id_pelanggan' => $dataTeknis->id_pelanggan,
                    'profile' => $originalProfile,
                    'result' => $result && $profileResult
                ]);

                return $result && $profileResult;
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
        $profile = $dataTeknis->profile_pppoe;
        
        if (empty($profile) || strtoupper($profile) === 'SUSPENDED') {
            $matches = [];
            if (preg_match('/(\d+)\s*Mbps/i', $langganan->layanan, $matches)) {
                $speed = $matches[1];
                $profile = "{$speed}Mbps-a";
            } else {
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
        
        $dueSubscriptions = Langganan::where('tgl_jatuh_tempo', $today)
            ->where('user_status', 'Aktif')
            ->get();
        
        $stats['total'] = $dueSubscriptions->count();
        
        Log::info('Ditemukan langganan jatuh tempo hari ini', [
            'count' => $stats['total'],
            'date' => $today
        ]);
            
        foreach ($dueSubscriptions as $langganan) {
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
                $langganan->user_status = 'Suspend';
                $langganan->save();
                
                Log::info('Mensuspend pelanggan jatuh tempo hari ini', [
                    'pelanggan_id' => $langganan->pelanggan_id,
                    'tgl_jatuh_tempo' => $langganan->tgl_jatuh_tempo
                ]);
                
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