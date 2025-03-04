<?php

namespace App\Filament\Resources\DataTeknisResource\Pages;

use App\Filament\Resources\DataTeknisResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
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
            ->body('Data Teknis baru telah dibuat. Klik tombol di bawah untuk melihat detailnya.')
            ->actions([
                Action::make('Lihat Data') // Menggunakan Action yang benar
                    ->url($this->getResource()::getUrl('edit', ['record' => $this->record]))
                    ->button(),
            ])
            ->send();
    }
}
