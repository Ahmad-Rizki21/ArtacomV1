<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;


class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Hapus Data Invoice')
                ->modalDescription('Apakah Anda yakin ingin menghapus Data Invoice ini? Tindakan ini tidak dapat dibatalkan.')
                ->modalSubmitActionLabel('Ya, Hapus')
                ->modalCancelActionLabel('Batal')
                ->successNotificationTitle('🗑️ Data Invoice Berhasil Dihapus!')
                ->after(function () {
                    Notification::make()
                        ->success()
                        ->title('🗑️ Data Invoice Telah Dihapus!')
                        ->body('Data Invoice ini telah dihapus secara permanen.')
                        ->send();
                }),
        ];
    }


    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->color('success')
            ->title('✅ Data Invoice Berhasil Diperbarui!')
            ->body('Perubahan pada Data Invoice telah disimpan. Klik tombol di bawah untuk melihat detailnya.')
            ->actions([
                Action::make('Lihat Detail Invoice')
                    ->url($this->getResource()::getUrl('edit', ['record' => $this->record]))
                    ->button(),
            ])
            ->send();
    }
}
