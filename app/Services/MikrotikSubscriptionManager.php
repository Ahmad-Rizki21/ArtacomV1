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
        Log::debug('MikrotikSubscriptionManager initialized');
    }

    /**
     * Membuat PPPoE Secret di Mikrotik berdasarkan data teknis.
     * Fungsi ini tidak berubah.
     *
     * @param \App\Models\DataTeknis $dataTeknis
     * @return bool
     */
    public function createPppoeSecretOnMikrotik($dataTeknis)
    {
        Log::debug('Memulai createPppoeSecretOnMikrotik untuk data teknis ID: ' . ($dataTeknis->id ?? 'unknown'));

        if (!$dataTeknis || empty($dataTeknis->id_pelanggan)) {
            Log::error('Data teknis tidak lengkap untuk membuat PPPoE secret.', ['data_teknis' => $dataTeknis ? $dataTeknis->toArray() : null]);
            return false;
        }

        // Dapatkan server berdasarkan OLT dari data teknis
        $oltName = $dataTeknis->olt === 'Lainnya' ? $dataTeknis->olt_custom : $dataTeknis->olt;
        if (empty($oltName)) {
            Log::error('Nama OLT pada data teknis kosong untuk pembuatan secret.', ['data_teknis_id' => $dataTeknis->id]);
            return false;
        }
        
        $server = MikrotikServer::where('name', $oltName)->where('is_active', true)->first();
        if (!$server) {
            Log::error('Server MikroTik tidak ditemukan atau tidak aktif untuk pembuatan secret.', ['olt_name_searched' => $oltName]);
            return false;
        }

        $data = [
            'name' => $dataTeknis->id_pelanggan,
            'password' => $dataTeknis->password_pppoe,
            'profile' => $dataTeknis->profile_pppoe,
            'remote-address' => $dataTeknis->ip_pelanggan,
            'service' => 'pppoe',
            'comment' => 'Dibuat otomatis oleh sistem pada ' . Carbon::now()->toDateTimeString()
        ];

        Log::info('Mempersiapkan pembuatan PPPoE secret di Mikrotik.', ['data' => $data, 'server' => $server->name]);

        try {
            // Panggil service dengan menyertakan server target
            $result = $this->mikrotikService->createPppoeSecret($data, $server);

            if ($result) {
                Log::info('PPPoE secret berhasil diproses di Mikrotik.', ['id_pelanggan' => $dataTeknis->id_pelanggan]);
            } else {
                Log::error('Gagal memproses PPPoE secret di Mikrotik.', ['id_pelanggan' => $dataTeknis->id_pelanggan]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Exception saat membuat PPPoE secret: ' . $e->getMessage(), [
                'id_pelanggan' => $dataTeknis->id_pelanggan,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * [REVISI UTAMA] Menangani update status langganan dengan pengecekan terlebih dahulu.
     *
     * @param Langganan $langganan
     * @param string $action (suspend|activate)
     * @return bool
     */
    public function handleSubscriptionStatus(Langganan $langganan, string $action): bool
    {
        $dataTeknis = optional($langganan->pelanggan)->dataTeknis;
        
        if (!$dataTeknis || !$dataTeknis->id_pelanggan) {
            Log::error('Tidak dapat menemukan data teknis atau ID Pelanggan.', ['langganan_id' => $langganan->id]);
            return false;
        }

        $oltName = $dataTeknis->olt === 'Lainnya' ? $dataTeknis->olt_custom : $dataTeknis->olt;
        if (empty($oltName)) {
            Log::error('Nama OLT pada data teknis kosong.', ['data_teknis_id' => $dataTeknis->id]);
            return false;
        }
        
        $server = MikrotikServer::where('name', $oltName)->where('is_active', true)->first();
        if (!$server) {
            Log::error('Server MikroTik target tidak ditemukan atau tidak aktif.', ['olt_name_searched' => $oltName, 'pelanggan_id' => $langganan->pelanggan_id]);
            return false;
        }

        try {
            // 1. Cek status saat ini di Mikrotik
            $secretDetails = $this->mikrotikService->getPppoeSecretDetails($dataTeknis->id_pelanggan, $server);

            if ($secretDetails === null) {
                Log::warning('Secret PPPoE tidak ditemukan di Mikrotik, tidak dapat melanjutkan update.', ['id_pelanggan' => $dataTeknis->id_pelanggan, 'server' => $server->name]);
                return false;
            }

            $currentProfile = $secretDetails['profile'] ?? 'unknown';
            $isDisabled = ($secretDetails['disabled'] ?? 'false') === 'true';

            // 2. Tentukan target status berdasarkan aksi
            if ($action === 'suspend') {
                $targetProfile = 'SUSPENDED';
                // 3. Bandingkan dan hanya update jika perlu
                if ($currentProfile !== $targetProfile || !$isDisabled) {
                    Log::info("Aksi SUSPEND: Status di Mikrotik belum sesuai. Memproses...", ['id_pelanggan' => $dataTeknis->id_pelanggan, 'current_profile' => $currentProfile, 'is_disabled' => $isDisabled]);
                    $result = $this->mikrotikService->updatePppoeProfile($dataTeknis->id_pelanggan, $targetProfile, $server);
                    $this->mikrotikService->removePppoeActiveConnection($dataTeknis->id_pelanggan, $server);
                    return $result;
                } else {
                    Log::info("Aksi SUSPEND: Status di Mikrotik sudah sesuai. Tidak ada aksi.", ['id_pelanggan' => $dataTeknis->id_pelanggan]);
                    return true; // Status sudah benar, anggap berhasil.
                }
            } elseif ($action === 'activate') {
                $targetProfile = $this->determineActiveProfile($dataTeknis, $langganan);
                // 3. Bandingkan dan hanya update jika perlu
                if ($currentProfile !== $targetProfile || $isDisabled) {
                    Log::info("Aksi ACTIVATE: Status di Mikrotik belum sesuai. Memproses...", ['id_pelanggan' => $dataTeknis->id_pelanggan, 'current_profile' => $currentProfile, 'target_profile' => $targetProfile, 'is_disabled' => $isDisabled]);
                    return $this->mikrotikService->updatePppoeProfile($dataTeknis->id_pelanggan, $targetProfile, $server);
                } else {
                    Log::info("Aksi ACTIVATE: Status di Mikrotik sudah sesuai. Tidak ada aksi.", ['id_pelanggan' => $dataTeknis->id_pelanggan]);
                    return true; // Status sudah benar, anggap berhasil.
                }
            }
        } catch (\Exception $e) {
            Log::error('Gagal update status Mikrotik karena exception.', [
                'id_pelanggan' => $dataTeknis->id_pelanggan,
                'server' => $server->name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }

        return false;
    }

    /**
     * [REVISI] Sinkronisasi status di Mikrotik.
     * Metode ini sekarang hanya menjadi jembatan ke `handleSubscriptionStatus` yang lebih cerdas.
     */
    public function syncMikrotikStatus(Langganan $langganan)
    {
        Log::info('Memulai sinkronisasi status Mikrotik untuk pelanggan.', [
            'pelanggan_id' => $langganan->pelanggan_id,
            'id_pelanggan' => optional(optional($langganan->pelanggan)->dataTeknis)->id_pelanggan,
            'status_db' => $langganan->user_status
        ]);
        
        $action = ($langganan->user_status === 'Aktif') ? 'activate' : 'suspend';
        
        return $this->handleSubscriptionStatus($langganan, $action);
    }

    /**
     * Menentukan profile aktif yang seharusnya digunakan.
     * Fungsi ini tidak berubah.
     */
    protected function determineActiveProfile($dataTeknis, $langganan)
    {
        $profile = $dataTeknis->profile_pppoe;
        
        if (empty($profile) || strtoupper($profile) === 'SUSPENDED') {
            Log::info('Profile di data teknis kosong atau SUSPENDED, menentukan profile dari data layanan/harga.', ['id_pelanggan' => $dataTeknis->id_pelanggan]);
            $matches = [];
            if (preg_match('/(\d+)\s*Mbps/i', $langganan->layanan, $matches)) {
                $speed = $matches[1];
                $profile = "{$speed}Mbps-a"; // Default suffix
            } else {
                // Fallback ke harga jika nama layanan tidak mengandung kecepatan
                $harga = $langganan->total_harga_layanan_x_pajak;
                if ($harga <= 150000) $profile = "10Mbps-a";
                elseif ($harga <= 200000) $profile = "20Mbps-a";
                elseif ($harga <= 250000) $profile = "30Mbps-a";
                else $profile = "50Mbps-a";
            }
            Log::info('Profile baru ditentukan.', ['determined_profile' => $profile]);
        } else {
            Log::info('Menggunakan profile dari data teknis.', ['profile' => $profile]);
        }
        
        return $profile;
    }
    
    /**
     * Memproses langganan yang jatuh tempo hari ini.
     * Fungsi ini tidak berubah.
     *
     * @return array Statistik hasil proses
     */
    public function processDueDateSubscriptions()
    {
        $today = Carbon::now()->format('Y-m-d');
        $stats = ['total' => 0, 'suspended' => 0, 'skipped' => 0, 'errors' => 0];
        
        Log::info("Memulai proses suspend untuk langganan yang jatuh tempo hari ini: {$today}");
        
        $dueSubscriptions = Langganan::where('tgl_jatuh_tempo', $today)
            ->where('user_status', 'Aktif')
            ->with('pelanggan.dataTeknis') // Eager load untuk efisiensi
            ->get();
        
        $stats['total'] = $dueSubscriptions->count();
        if ($stats['total'] === 0) {
            Log::info("Tidak ada langganan aktif yang jatuh tempo hari ini.");
            return $stats;
        }
        
        Log::info("Ditemukan {$stats['total']} langganan aktif yang jatuh tempo.");
            
        foreach ($dueSubscriptions as $langganan) {
            // Cek apakah ada invoice yang belum lunas untuk periode tagihan saat ini
            $hasUnpaidInvoice = Invoice::where('pelanggan_id', $langganan->pelanggan_id)
                ->where('tgl_jatuh_tempo', $langganan->tgl_jatuh_tempo)
                ->where('status_invoice', 'Menunggu Pembayaran')
                ->exists();
                
            if (!$hasUnpaidInvoice) {
                $stats['skipped']++;
                Log::info('Pelanggan tidak memiliki invoice jatuh tempo yang belum dibayar, proses suspend dilewati.', ['pelanggan_id' => $langganan->pelanggan_id]);
                continue;
            }
            
            try {
                // Update status di database terlebih dahulu
                $langganan->user_status = 'Suspend';
                $langganan->save();
                
                Log::info('Status pelanggan di DB diubah ke Suspend, memanggil handleSubscriptionStatus.', ['pelanggan_id' => $langganan->pelanggan_id]);
                
                if ($this->handleSubscriptionStatus($langganan, 'suspend')) {
                    $stats['suspended']++;
                } else {
                    $stats['errors']++;
                    Log::error('handleSubscriptionStatus gagal saat proses suspend.', ['pelanggan_id' => $langganan->pelanggan_id]);
                }
            } catch (\Exception $e) {
                $stats['errors']++;
                Log::error('Gagal suspend pelanggan karena exception.', [
                    'pelanggan_id' => $langganan->pelanggan_id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        Log::info('Proses suspend langganan jatuh tempo selesai.', $stats);
        return $stats;
    }
}
