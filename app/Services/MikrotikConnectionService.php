<?php

namespace App\Services;

use RouterOS\Client;
use RouterOS\Query;
use App\Models\MikrotikServer;
use Illuminate\Support\Facades\Log;

class MikrotikConnectionService
{
    /**
     * Membuat koneksi ke Mikrotik
     * 
     * @param MikrotikServer|null $server
     * @return Client|null
     */
    public function connect($server = null)
    {
        try {
            if (!$server) {
                $server = MikrotikServer::first();
                
                if (!$server) {
                    Log::error('Mikrotik Connection Error: No server found in database');
                    return null;
                }
            }
            
            Log::info('Connecting to Mikrotik', [
                'host' => $server->host_ip,
                'user' => $server->username,
                'port' => $server->port ?? 8728
            ]);
            
            $client = new Client([
                'host' => $server->host_ip,
                'user' => $server->username,
                'pass' => $server->password,
                'port' => $server->port ?? 8728,
                'timeout' => 10,
                'attempts' => 2
            ]);

            return $client;
        } catch (\Exception $e) {
            Log::error('Mikrotik Connection Error: ' . $e->getMessage(), [
                'host' => $server->host_ip ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Ubah profile PPPoE Secret dan aktifkan akun
     * 
     * @param string $secretName
     * @param string $newProfile
     * @return bool
     */
    public function updatePppoeProfile(string $secretName, string $newProfile)
    {
        $client = $this->connect();

        if (!$client) {
            return false;
        }

        try {
            // Log detail profile yang akan diupdate
            Log::info('Attempting to update PPPoE profile', [
                'secretName' => $secretName,
                'newProfile' => $newProfile,
                'isSuspended' => strtoupper($newProfile) === 'SUSPENDED'
            ]);
            
            // Cari ID secret berdasarkan name
            $query = new Query('/ppp/secret/print');
            $query->where('name', $secretName);
            $response = $client->query($query)->read();
            
            Log::info('Found PPPoE Secret', [
                'secretName' => $secretName,
                'response' => $response
            ]);

            if (empty($response)) {
                Log::warning('PPPoE Secret not found', [
                    'secretName' => $secretName
                ]);
                return false;
            }

            $secretId = $response[0]['.id'];
            $currentProfile = $response[0]['profile'] ?? 'unknown';
            $currentDisabled = isset($response[0]['disabled']) && $response[0]['disabled'] === 'true';
            
            Log::info('Current PPPoE status before update', [
                'secretName' => $secretName,
                'currentProfile' => $currentProfile,
                'isCurrentlyDisabled' => $currentDisabled,
                'newProfile' => $newProfile
            ]);
            
            // Update profile PPPoE
            $updateQuery = new Query('/ppp/secret/set');
            $updateQuery->equal('.id', $secretId);
            $updateQuery->equal('profile', $newProfile);
            
            // Check if we're suspending or activating
            $isSuspended = strtoupper($newProfile) === 'SUSPENDED';
            
            // Set disabled status based on profile
            if ($isSuspended) {
                $updateQuery->equal('disabled', 'yes');
            } else {
                $updateQuery->equal('disabled', 'no');
            }
            
            $updateResponse = $client->query($updateQuery)->read();
            
            Log::info('Update PPPoE Profile Response', [
                'secretName' => $secretName,
                'newProfile' => $newProfile,
                'disabled' => $isSuspended ? 'yes' : 'no',
                'response' => $updateResponse
            ]);
            
            // Verifikasi status terbaru
            $verifyQuery = new Query('/ppp/secret/print');
            $verifyQuery->where('name', $secretName);
            $verifyResponse = $client->query($verifyQuery)->read();
            
            if (!empty($verifyResponse)) {
                $newDisabled = isset($verifyResponse[0]['disabled']) && $verifyResponse[0]['disabled'] === 'true';
                $newProfileSet = isset($verifyResponse[0]['profile']) && $verifyResponse[0]['profile'] === $newProfile;
                
                Log::info('Verification after update', [
                    'secretName' => $secretName,
                    'profile_updated' => $newProfileSet,
                    'is_disabled' => $newDisabled,
                    'expected_disabled' => $isSuspended,
                    'current_profile' => $verifyResponse[0]['profile'] ?? 'unknown'
                ]);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Error updating PPPoE Profile: ' . $e->getMessage(), [
                'secretName' => $secretName,
                'newProfile' => $newProfile,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
    
    /**
     * Nonaktifkan PPPoE Secret dengan mengubah profile ke SUSPENDED dan set disabled=yes
     * 
     * @param string $secretName
     * @return bool
     */
    public function disablePppoeSecret(string $secretName)
    {
        $client = $this->connect();

        if (!$client) {
            return false;
        }

        try {
            // Cari ID secret berdasarkan name
            $query = new Query('/ppp/secret/print');
            $query->where('name', $secretName);
            $response = $client->query($query)->read();
            
            if (empty($response)) {
                Log::warning('PPPoE Secret not found for disable', [
                    'secretName' => $secretName
                ]);
                return false;
            }
            
            $secretId = $response[0]['.id'];
            $currentProfile = $response[0]['profile'] ?? 'unknown';
            
            Log::info('Disabling PPPoE Secret', [
                'secretName' => $secretName,
                'currentProfile' => $currentProfile
            ]);
            
            // Set disabled=yes dan ubah profile ke SUSPENDED
            $updateQuery = new Query('/ppp/secret/set');
            $updateQuery->equal('.id', $secretId);
            $updateQuery->equal('disabled', 'yes');
            $updateQuery->equal('profile', 'SUSPENDED');
            $response = $client->query($updateQuery)->read();
            
            Log::info('Disable PPPoE Secret response', [
                'secretName' => $secretName,
                'method' => 'set disabled=yes and profile=SUSPENDED',
                'response' => $response
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Error disabling PPPoE Secret: ' . $e->getMessage(), [
                'secretName' => $secretName
            ]);
            return false;
        }
    }

    /**
     * Aktifkan PPPoE Secret dengan mengubah profile sesuai parameter dan set disabled=no
     * 
     * @param string $secretName
     * @param string|null $originalProfile Profile yang akan digunakan, jika null akan menggunakan profile existing
     * @return bool
     */
    public function enablePppoeSecret(string $secretName, string $originalProfile = null)
    {
        $client = $this->connect();

        if (!$client) {
            return false;
        }

        try {
            // Cari ID secret berdasarkan name
            $query = new Query('/ppp/secret/print');
            $query->where('name', $secretName);
            $response = $client->query($query)->read();
            
            if (empty($response)) {
                Log::warning('PPPoE Secret not found for enable', [
                    'secretName' => $secretName
                ]);
                return false;
            }
            
            $secretId = $response[0]['.id'];
            $currentProfile = $response[0]['profile'] ?? 'unknown';
            
            Log::info('Enabling PPPoE Secret', [
                'secretName' => $secretName,
                'currentProfile' => $currentProfile,
                'originalProfile' => $originalProfile
            ]);
            
            // Set disabled=no dan update profile jika disediakan
            $updateQuery = new Query('/ppp/secret/set');
            $updateQuery->equal('.id', $secretId);
            $updateQuery->equal('disabled', 'no');
            
            // Jika original profile diberikan dan current profile adalah SUSPENDED, kembalikan ke profile asli
            if ($originalProfile && strtoupper($currentProfile) === 'SUSPENDED') {
                $updateQuery->equal('profile', $originalProfile);
                Log::info('Restoring original profile', [
                    'secretName' => $secretName,
                    'fromProfile' => $currentProfile,
                    'toProfile' => $originalProfile
                ]);
            }
            
            $response = $client->query($updateQuery)->read();
            
            Log::info('Enable PPPoE Secret response', [
                'secretName' => $secretName,
                'method' => 'set disabled=no' . ($originalProfile ? ' and restore profile' : ''),
                'response' => $response
            ]);
            
            // Verifikasi hasil
            $verifyQuery = new Query('/ppp/secret/print');
            $verifyQuery->where('name', $secretName);
            $verifyResponse = $client->query($verifyQuery)->read();
            
            if (!empty($verifyResponse)) {
                $newDisabled = isset($verifyResponse[0]['disabled']) && $verifyResponse[0]['disabled'] === 'true';
                $currentProfile = $verifyResponse[0]['profile'] ?? 'unknown';
                
                Log::info('Verification after enable', [
                    'secretName' => $secretName,
                    'current_profile' => $currentProfile,
                    'is_disabled' => $newDisabled,
                    'expected_disabled' => false
                ]);
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('Error enabling PPPoE Secret: ' . $e->getMessage(), [
                'secretName' => $secretName
            ]);
            return false;
        }
    }
    
    /**
     * Dapatkan detail profil PPPoE saat ini
     * 
     * @param string $secretName
     * @return array|null
     */
    public function getPppoeSecretDetails(string $secretName)
    {
        $client = $this->connect();

        if (!$client) {
            return null;
        }

        try {
            // Cari ID secret berdasarkan name
            $query = new Query('/ppp/secret/print');
            $query->where('name', $secretName);
            $response = $client->query($query)->read();

            if (empty($response)) {
                Log::warning('PPPoE Secret not found', [
                    'secretName' => $secretName
                ]);
                return null;
            }

            Log::info('PPPoE Secret details', [
                'secretName' => $secretName,
                'details' => $response[0]
            ]);

            return $response[0];
        } catch (\Exception $e) {
            Log::error('Error getting PPPoE Secret details: ' . $e->getMessage(), [
                'secretName' => $secretName
            ]);
            return null;
        }
    }
}