<?php

namespace App\Listeners;

use App\Events\PelangganCreated;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Exception;

class SendNocNotificationToCreateDataTeknis implements ShouldQueue
{
    public $deleteWhenMissingModels = true; // Hapus tugas jika model hilang

    public function handle(PelangganCreated $event)
    {
        try {
            $pelanggan = $event->pelanggan;
            Log::debug('Pelanggan data:', ['pelanggan' => $pelanggan->toArray()]);

            $nocUsers = User::role('Noc')->get();

            if ($nocUsers->isEmpty()) {
                Log::warning('Tidak ada pengguna dengan role Noc yang ditemukan.');
                return;
            }

            foreach ($nocUsers as $user) {
                if (!$pelanggan->nama || !$pelanggan->id) {
                    Log::error('Data pelanggan tidak lengkap.', ['pelanggan_id' => $pelanggan->id]);
                    continue;
                }

                Notification::make()
                    ->title('Tindakan Diperlukan: Buat Data Teknis')
                    ->body("Pelanggan baru '{$pelanggan->nama}' telah dibuat oleh tim Sales pada " . now()->format('d M Y') . ". Mohon segera lengkapi Data Teknis agar proses Langganan dan Invoice dapat dilanjutkan.")
                    ->warning()
                    ->persistent()
                    ->actions([
                        \Filament\Notifications\Actions\Action::make('Buat Data Teknis')
                            ->url("/admin/data-teknis/create?pelanggan_id={$pelanggan->id}")
                            ->button(),
                    ])
                    ->sendToDatabase($user);

                Log::info('Notifikasi buat Data Teknis dikirim ke NOC', ['user_id' => $user->id, 'pelanggan_id' => $pelanggan->id]);
            }
        } catch (Exception $e) {
            Log::error('Error dalam pengiriman notifikasi:', ['exception' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            throw $e; // Lempar error agar queue mencatat kegagalan
        }
    }

    public function failed(PelangganCreated $event, $exception)
    {
        Log::error('Notifikasi gagal diproses:', ['exception' => $exception->getMessage(), 'event' => $event->pelanggan->toArray()]);
    }
}