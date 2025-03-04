<?php

namespace App\Filament\Resources\PelangganResource\Pages;

use App\Filament\Resources\PelangganResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;

class EditPelanggan extends EditRecord
{
    protected static string $resource = PelangganResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Hapus Data')
                ->modalDescription('Apakah Anda yakin ingin menghapus Data Pelanggan ini? Tindakan ini tidak dapat dibatalkan.')
                ->modalSubmitActionLabel('Ya, Hapus')
                ->modalCancelActionLabel('Batal')
                ->successNotificationTitle('🗑️ Data Pelanggan Berhasil Dihapus!')
                ->after(function () {
                    Notification::make()
                        ->success()
                        ->title('🗑️ Data Pelanggan Telah Dihapus!')
                        ->body('Data Pelanggan ini telah dihapus secara permanen.')
                        ->send();
                }),
        ];
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->color('success')
            ->title('✅ Data Pelanggan Berhasil Diperbarui!')
            ->body('Perubahan pada Data Pelanggan telah disimpan. Klik tombol di bawah untuk melihat detailnya.')
            ->actions([
                Action::make('Lihat Data Pelanggan')
                    ->url($this->getResource()::getUrl('edit', ['record' => $this->record]))
                    ->button(),
            ])
            ->send();
    }

}
