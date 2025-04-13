<?php

namespace App\Listeners;

use App\Events\LanggananCreatedWithoutDataTeknis;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendNocNotification implements ShouldQueue
{
    public function handle(LanggananCreatedWithoutDataTeknis $event)
{
    $langganan = $event->langganan;

    $dataTeknis = \App\Models\DataTeknis::where('pelanggan_id', $langganan->pelanggan_id)->first();
    Log::info('Memeriksa Data Teknis di Listener', [
        'langganan_id' => $langganan->id,
        'pelanggan_id' => $langganan->pelanggan_id,
        'data_teknis_exists' => $dataTeknis ? true : false
    ]);

    if ($dataTeknis) {
        Log::info('Data Teknis sudah ada, notifikasi tidak dikirim.', ['langganan_id' => $langganan->id]);
        return;
    }

    $nocUsers = User::role('Noc')->get();
    Log::info('Pengguna dengan role Noc ditemukan', [
        'count' => $nocUsers->count(),
        'users' => $nocUsers->pluck('id')->toArray()
    ]);

    if ($nocUsers->isEmpty()) {
        Log::warning('Tidak ada pengguna dengan role Noc yang ditemukan.');
        return;
    }

    foreach ($nocUsers as $user) {
        Notification::make()
            ->title('Peringatan: Data Teknis Belum Diisi')
            ->body("Langganan untuk pelanggan #{$langganan->pelanggan_id} ({$langganan->pelanggan->nama}) telah dibuat oleh Finance pada " . now()->format('d M Y') . ", tetapi Data Teknis belum diisi. NOC wajib mengisi Data Teknis agar proses Invoice dapat berlanjut.")
            ->warning()
            ->persistent()
            ->actions([
                \Filament\Notifications\Actions\Action::make('Lihat Langganan')
                    ->url("/admin/langganan/{$langganan->id}/edit")
                    ->button(),
            ])
            ->sendToDatabase($user);
        Log::info('Notifikasi dikirim ke pengguna Noc', ['user_id' => $user->id, 'langganan_id' => $langganan->id]);
    }
}
}