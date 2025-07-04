<?php

namespace App\Services;

use RouterOS\Client;
use RouterOS\Query;
use App\Models\MikrotikServer;
use App\Models\ServerMetric;
use Illuminate\Support\Facades\Log;

class MikrotikConnectionService
{
    /**
     * Menyimpan koneksi client yang aktif selama script berjalan untuk digunakan kembali.
     * @var array
     */
    private $activeClients = [];

    /**
     * Membuat koneksi ke Mikrotik atau menggunakan kembali koneksi yang ada.
     *
     * @param MikrotikServer|null $server
     * @return Client|null
     */
    public function connect(MikrotikServer $server = null)
    {
        try {
            if (!$server) {
                Log::error('Mikrotik Connection Error: Server target tidak ditentukan.');
                return null;
            }

            // Cek apakah koneksi untuk server ini sudah ada. Jika ya, gunakan kembali.
            if (isset($this->activeClients[$server->id])) {
                Log::info('Menggunakan kembali koneksi Mikrotik yang ada.', ['server' => $server->name]);
                return $this->activeClients[$server->id];
            }

            $port = isset($server->port) ? (int)$server->port : 8728;

            Log::info('Membuat koneksi baru ke Mikrotik.', [
                'server' => $server->name,
                'host' => $server->host_ip,
                'user' => $server->username,
                'port' => $port
            ]);

            $client = new Client([
                'host' => $server->host_ip,
                'user' => $server->username,
                'pass' => $server->password,
                'port' => $port,
                'timeout' => 30, // Timeout koneksi dalam detik
                'attempts' => 2, // Jumlah percobaan koneksi
            ]);

            // Simpan client yang baru dibuat ke dalam array untuk digunakan kembali.
            $this->activeClients[$server->id] = $client;

            return $client;

        } catch (\Exception $e) {
            Log::error('Mikrotik Connection Error: ' . $e->getMessage(), [
                'server' => $server->name ?? 'unknown',
                'host' => $server->host_ip ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * [DINONAKTIFKAN] Mengumpulkan dan menyimpan metrik dari server Mikrotik.
     * Logika di dalam fungsi ini dinonaktifkan sesuai permintaan untuk menghindari error
     * 'Undefined array key 0' dan 'Stream timed out' yang muncul di log.
     *
     * @param MikrotikServer|null $server
     * @return bool
     */
    public function collectMetrics(MikrotikServer $server = null)
    {
        Log::info('Fungsi collectMetrics dipanggil, namun telah dinonaktifkan sesuai konfigurasi.', ['server' => $server->name ?? 'unknown']);
        // Mengembalikan true untuk menandakan tugas selesai tanpa error.
        // Fungsi ini sengaja dikosongkan untuk mengatasi error pada log dan karena tidak lagi dibutuhkan.
        return true;
    }

    /**
     * Ubah profile PPPoE Secret dan status aktif/nonaktif akun.
     *
     * @param string $secretName
     * @param string $newProfile
     * @param MikrotikServer $server
     * @return bool
     */
    public function updatePppoeProfile(string $secretName, string $newProfile, MikrotikServer $server): bool
    {
        if (!($client = $this->connect($server))) {
            return false;
        }

        try {
            $isSuspended = (strtoupper($newProfile) === 'SUSPENDED');
            Log::info('Attempting to update PPPoE profile.', [
                'secretName' => $secretName,
                'newProfile' => $newProfile,
                'isSuspended' => $isSuspended,
                'server' => $server->name
            ]);
            
            $secret = $this->getPppoeSecretDetails($secretName, $server);

            if (empty($secret)) {
                Log::warning('PPPoE Secret not found, cannot update.', ['secretName' => $secretName, 'server' => $server->name]);
                return false;
            }

            $secretId = $secret['.id'];
            
            $updateQuery = new Query('/ppp/secret/set');
            $updateQuery->equal('.id', $secretId);
            $updateQuery->equal('profile', $newProfile);
            $updateQuery->equal('disabled', $isSuspended ? 'yes' : 'no');
            
            $client->query($updateQuery)->read();
            
            Log::info('Successfully sent update command for PPPoE Profile.', ['secretName' => $secretName, 'newProfile' => $newProfile]);
            return true;

        } catch (\Exception $e) {
            Log::error('Error updating PPPoE Profile: ' . $e->getMessage(), [
                'secretName' => $secretName,
                'newProfile' => $newProfile,
                'server' => $server->name,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Hapus koneksi PPPoE yang sedang aktif untuk memaksa logout.
     *
     * @param string $username
     * @param MikrotikServer $server
     * @return bool
     */
    public function removePppoeActiveConnection(string $username, MikrotikServer $server): bool
    {
        if (!($client = $this->connect($server))) {
            return false;
        }

        try {
            $query = new Query('/ppp/active/print');
            $query->where('name', $username);
            $activeConnections = $client->query($query)->read();

            if (empty($activeConnections)) {
                Log::info('No active PPPoE connection found for user. No action needed.', ['username' => $username, 'server' => $server->name]);
                return true;
            }

            $connectionId = $activeConnections[0]['.id'];
            Log::info('Found active PPPoE connection. Removing...', ['username' => $username, 'connection_id' => $connectionId, 'server' => $server->name]);

            $removeQuery = new Query('/ppp/active/remove');
            $removeQuery->equal('.id', $connectionId);
            $client->query($removeQuery)->read();

            Log::info('Successfully removed active PPPoE connection.', ['username' => $username, 'server' => $server->name]);
            return true;

        } catch (\Exception $e) {
            Log::error('Error removing active PPPoE connection: ' . $e->getMessage(), [
                'username' => $username,
                'server' => $server->name,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * [PERUBAHAN PENTING] Dapatkan detail profil PPPoE saat ini dari server tertentu.
     *
     * @param string $secretName
     * @param MikrotikServer $server
     * @return array|null
     */
    public function getPppoeSecretDetails(string $secretName, MikrotikServer $server)
    {
        if (!($client = $this->connect($server))) {
            return null;
        }

        try {
            $query = new Query('/ppp/secret/print');
            $query->where('name', $secretName);
            $response = $client->query($query)->read();

            if (empty($response)) {
                Log::debug('PPPoE Secret not found on getPppoeSecretDetails.', ['secretName' => $secretName, 'server' => $server->name]);
                return null;
            }
            
            return $response[0];

        } catch (\Exception $e) {
            Log::error('Error getting PPPoE Secret details: ' . $e->getMessage(), [
                'secretName' => $secretName,
                'server' => $server->name,
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Membuat secret PPPoE baru di server tertentu.
     *
     * @param array $data Data untuk secret baru.
     * @param MikrotikServer $server Server tujuan.
     * @return bool
     */
    public function createPppoeSecret(array $data, MikrotikServer $server): bool
    {
        if (!($client = $this->connect($server))) {
            Log::error('Gagal koneksi ke Mikrotik saat akan membuat secret.', ['server' => $server->name]);
            return false;
        }

        try {
            $existing = $this->getPppoeSecretDetails($data['name'], $server);

            if (!empty($existing)) {
                Log::warning('PPPoE secret dengan nama ini sudah ada, proses pembuatan dilewati.', ['name' => $data['name'], 'server' => $server->name]);
                return true; // Anggap berhasil jika sudah ada
            }

            $createQuery = new Query('/ppp/secret/add');
            foreach ($data as $key => $value) {
                if ($value !== null) { // Pastikan tidak mengirim nilai null
                    $createQuery->equal($key, $value);
                }
            }

            $client->query($createQuery)->read();
            Log::info('Berhasil membuat PPPoE secret baru.', ['name' => $data['name'], 'server' => $server->name]);
            return true;

        } catch (\Exception $e) {
            Log::error('Exception saat membuat PPPoE secret: ' . $e->getMessage(), [
                'data' => $data,
                'server' => $server->name,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
}
