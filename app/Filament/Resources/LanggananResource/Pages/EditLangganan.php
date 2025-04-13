<?php

namespace App\Filament\Resources\LanggananResource\Pages;

use App\Filament\Resources\LanggananResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use Illuminate\Database\Eloquent\Model;

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

    protected function mutateFormDataBeforeFill(array $data): array
{
    return $data;
}

protected function mutateFormDataBeforeSave(array $data): array
{
    // Pastikan pelanggan_id dan id_pelanggan tidak berubah
    $data['pelanggan_id'] = $this->record->pelanggan_id;
    $data['id_pelanggan'] = $this->record->id_pelanggan;

    return $data;
}
    
protected function handleRecordUpdate(Model $record, array $data): Model
{
    // Pastikan pelanggan_id dan id_pelanggan tidak berubah
    $data['pelanggan_id'] = $record->pelanggan_id;
    $data['id_pelanggan'] = $record->id_pelanggan;

    $record->update($data);

    return $record;
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