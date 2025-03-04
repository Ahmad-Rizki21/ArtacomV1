<?php

namespace App\Filament\Resources\LanggananResource\Pages;

use App\Filament\Resources\LanggananResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;

class CreateLangganan extends CreateRecord
{
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    protected static string $resource = LanggananResource::class;

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->color('success')
            ->title('🎉 Data Berlangganan Baru Telah Ditambahkan!')
            ->body('Data Berlangganan baru telah dibuat. Klik tombol di bawah untuk melihat detailnya.')
            ->actions([
                Action::make('Lihat Data') // Menggunakan Action yang benar
                    ->url($this->getResource()::getUrl('edit', ['record' => $this->record]))
                    ->button(),
            ])
            ->send();
    }
}


