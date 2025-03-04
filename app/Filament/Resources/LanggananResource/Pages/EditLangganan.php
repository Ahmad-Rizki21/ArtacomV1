<?php

namespace App\Filament\Resources\LanggananResource\Pages;

use App\Filament\Resources\LanggananResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;

class EditLangganan extends EditRecord
{
    protected static string $resource = LanggananResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
            ->modalHeading('Konfirmasi Hapus Data Berlangganan')
            ->modalDescription('Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.')
            ->modalSubmitActionLabel('Ya, Hapus')
            ->modalCancelActionLabel('Batal')
            ->successNotificationTitle('🗑️ Pelanggan Berhasil Dihapus!')
                ->after(function () {
                    Notification::make()
                ->success()
                ->title('🗑️ Data Berlangganan Telah Dihapus!')
                ->body('Pelanggan ini telah dihapus secara permanen.')
                ->send();
            }),
            
        ];
    }


    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->color('success')
            ->title('✅ Data Berlangganan Berhasil Diperbarui!')
            ->body('Perubahan pada Data Berlangganan telah disimpan. Klik tombol di bawah untuk melihat detailnya.')
            ->actions([
                Action::make('Lihat Detail')
                    ->url($this->getResource()::getUrl('edit', ['record' => $this->record]))
                    ->button(),
            ])
            ->send();
    }
}
