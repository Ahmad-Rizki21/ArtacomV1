<?php

namespace App\Filament\Resources\DataTeknisResource\Pages;

use App\Filament\Resources\DataTeknisResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;

class EditDataTeknis extends EditRecord
{
    protected static string $resource = DataTeknisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Hapus Data Teknis')
                ->modalDescription('Apakah Anda yakin ingin menghapus Data Teknis ini? Tindakan ini tidak dapat dibatalkan.')
                ->modalSubmitActionLabel('Ya, Hapus')
                ->modalCancelActionLabel('Batal')
                ->successNotificationTitle('🗑️ Data Teknis Berhasil Dihapus!')
                ->after(function () {
                    Notification::make()
                        ->success()
                        ->title('🗑️ Data Teknis Telah Dihapus!')
                        ->body('Data Teknis ini telah dihapus secara permanen.')
                        ->send();
                }),
        ];
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->color('success')
            ->title('✅ Data Teknis Berhasil Diperbarui!')
            ->body('Perubahan pada Data Teknis telah disimpan. Klik tombol di bawah untuk melihat detailnya.')
            ->actions([
                Action::make('Lihat Data Teknis')
                    ->url($this->getResource()::getUrl('edit', ['record' => $this->record]))
                    ->button(),
            ])
            ->send();
    }
}
