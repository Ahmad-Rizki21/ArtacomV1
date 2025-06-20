<?php

namespace App\Filament\Resources\DataTeknisResource\Pages;

use App\Filament\Resources\DataTeknisResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Services\MikrotikSubscriptionManager;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Actions\Action;

class CreateDataTeknis extends CreateRecord
{
    protected static string $resource = DataTeknisResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->color('success')
            ->title('🎉 Data Teknis Berhasil Dibuat!')
            ->body('Data Teknis baru telah dibuat dan PPPoE secret telah ditambahkan ke Mikrotik. Klik tombol di bawah untuk melihat detailnya.')
            ->actions([
                Action::make('Lihat Data')
                    ->url($this->getResource()::getUrl('edit', ['record' => $this->record]))
                    ->button(),
            ])
            ->send();
    }

    protected function afterSave(): void
    {
        parent::afterSave();

        Log::debug('Memulai proses afterSave untuk data teknis ID: ' . $this->record->id, [
            'data_teknis' => $this->record->toArray()
        ]);

        try {
            $mikrotikManager = app(MikrotikSubscriptionManager::class);

            // Validasi data teknis
            if (!$this->validateDataTeknis($this->record)) {
                $this->notify('danger', 'Data teknis tidak lengkap untuk membuat PPPoE secret di Mikrotik.');
                Log::error('Validasi data teknis gagal untuk ID: ' . $this->record->id, [
                    'data_teknis' => $this->record->toArray()
                ]);
                return;
            }

            Log::info('Mencoba membuat PPPoE secret untuk data teknis ID: ' . $this->record->id);

            // Buat PPPoE secret di Mikrotik
            $result = $mikrotikManager->createPppoeSecretOnMikrotik($this->record);

            if ($result) {
                $this->notify('success', 'PPPoE secret berhasil dibuat di Mikrotik.');
                Log::info('PPPoE secret berhasil dibuat untuk data teknis ID: ' . $this->record->id, [
                    'data_teknis' => $this->record->toArray()
                ]);
            } else {
                $this->notify('danger', 'Gagal membuat PPPoE secret di Mikrotik.');
                Log::error('PPPoE secret gagal dibuat untuk data teknis ID: ' . $this->record->id, [
                    'data_teknis' => $this->record->toArray()
                ]);
            }
        } catch (\Exception $e) {
            $this->notify('error', 'Terjadi kesalahan saat membuat PPPoE secret di Mikrotik.');
            Log::error('Exception saat membuat PPPoE secret di afterSave: ' . $e->getMessage(), [
                'data_teknis_id' => $this->record->id,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Validasi data teknis sebelum membuat PPPoE secret
     *
     * @param \App\Models\DataTeknis $dataTeknis
     * @return bool
     */
    protected function validateDataTeknis($dataTeknis): bool
    {
        $requiredFields = [
            'id_pelanggan',
            'password_pppoe',
            'profile_pppoe',
            'ip_pelanggan'
        ];

        foreach ($requiredFields as $field) {
            if (empty($dataTeknis->$field)) {
                Log::warning("Field $field kosong pada data teknis ID: $dataTeknis->id", [
                    'data_teknis' => $dataTeknis->toArray()
                ]);
                return false;
            }
        }

        Log::debug('Validasi data teknis berhasil untuk ID: ' . $dataTeknis->id);
        return true;
    }
}