<?php

namespace App\Filament\Resources\PelangganResource\Pages;

use App\Filament\Resources\PelangganResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action; // Perbaikan namespace di sini

class CreatePelanggan extends CreateRecord
{
    protected static string $resource = PelangganResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->color('success')
            ->title('🎉 Data Pelanggan Berhasil Dibuat!')
            ->body('Data pelanggan baru telah dibuat. Klik tombol di bawah untuk melihat detailnya.')
            ->actions([
                Action::make('Lihat Data') // Menggunakan Action yang benar
                    ->url($this->getResource()::getUrl('edit', ['record' => $this->record]))
                    ->button(),
            ])
            ->send();
    }
}
