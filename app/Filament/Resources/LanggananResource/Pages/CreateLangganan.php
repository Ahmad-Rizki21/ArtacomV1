<?php

namespace App\Filament\Resources\LanggananResource\Pages;

use App\Filament\Resources\LanggananResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use App\Models\Langganan;
use App\Models\Pelanggan;
use App\Models\DataTeknis;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;

class CreateLangganan extends CreateRecord
{
    protected static string $resource = LanggananResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (!isset($data['pelanggan_id'])) {
            throw ValidationException::withMessages(['pelanggan_id' => 'Pelanggan ID tidak ditemukan dalam data.']);
        }

        $pelangganId = $data['pelanggan_id'];

        // Cek duplikat berdasarkan pelanggan_id (tanpa memfilter status)
        $existingLanggananByPelanggan = Langganan::where('pelanggan_id', $pelangganId)->first();

        // Ambil data teknis untuk memeriksa id_pelanggan
        $dataTeknis = DataTeknis::where('pelanggan_id', $pelangganId)->first();

        if ($dataTeknis && $dataTeknis->id_pelanggan) {
            $existingLanggananByIdPelanggan = Langganan::where('id_pelanggan', $dataTeknis->id_pelanggan)->first();

            if ($existingLanggananByIdPelanggan) {
                $pelanggan = Pelanggan::find($pelangganId);
                $namaPelanggan = $pelanggan ? $pelanggan->nama : 'ID #' . $pelangganId;

                Log::warning('Mencoba membuat langganan duplikat berdasarkan id_pelanggan', [
                    'pelanggan_id' => $pelangganId,
                    'id_pelanggan' => $dataTeknis->id_pelanggan,
                    'existing_langganan_id' => $existingLanggananByIdPelanggan->id,
                    'nama_pelanggan' => $namaPelanggan
                ]);

                Notification::make()
                    ->warning()
                    ->title('Pelanggan Sudah Memiliki Langganan')
                    ->body("Pelanggan {$namaPelanggan} sudah memiliki langganan dengan ID Pelanggan #{$dataTeknis->id_pelanggan}. Satu pelanggan hanya boleh memiliki satu langganan.")
                    ->persistent()
                    ->actions([
                        Action::make('lihat')
                            ->label('Lihat Langganan')
                            ->url(LanggananResource::getUrl('edit', ['record' => $existingLanggananByIdPelanggan->id]))
                            ->button(),
                    ])
                    ->send();

                throw ValidationException::withMessages([
                    'pelanggan_id' => ["Pelanggan {$namaPelanggan} sudah memiliki langganan berdasarkan ID Pelanggan."],
                ]);
            }
        }

        if ($existingLanggananByPelanggan) {
            $pelanggan = Pelanggan::find($pelangganId);
            $namaPelanggan = $pelanggan ? $pelanggan->nama : 'ID #' . $pelangganId;

            Log::warning('Mencoba membuat langganan duplikat', [
                'pelanggan_id' => $pelangganId,
                'existing_langganan_id' => $existingLanggananByPelanggan->id,
                'nama_pelanggan' => $namaPelanggan
            ]);

            Notification::make()
                ->warning()
                ->title('Pelanggan Sudah Memiliki Langganan')
                ->body("Pelanggan {$namaPelanggan} sudah memiliki langganan dengan ID #{$existingLanggananByPelanggan->id}. Satu pelanggan hanya boleh memiliki satu langganan.")
                ->persistent()
                ->actions([
                    Action::make('lihat')
                        ->label('Lihat Langganan')
                        ->url(LanggananResource::getUrl('edit', ['record' => $existingLanggananByPelanggan->id]))
                        ->button(),
                ])
                ->send();

            throw ValidationException::withMessages([
                'pelanggan_id' => ["Pelanggan {$namaPelanggan} sudah memiliki langganan di database."],
            ]);
        }

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        try {
            $record = parent::handleRecordCreation($data);

            // Set status awal langganan baru (sesuaikan dengan kebutuhan)
            $record->user_status = 'Suspend'; // Atau 'Aktif' jika langsung aktif
            $record->save();

            // Optional: Tambahkan log untuk debugging
            Log::info('Langganan baru berhasil dibuat', [
                'pelanggan_id' => $record->pelanggan_id,
                'id_pelanggan' => $record->id_pelanggan,
                'user_status' => $record->user_status
            ]);

            return $record;
        } catch (QueryException $e) {
            if ($e->errorInfo[1] == 1062) { // Kode error untuk duplicate entry
                $pelangganId = $data['pelanggan_id'];
                $pelanggan = Pelanggan::find($pelangganId);
                $namaPelanggan = $pelanggan ? $pelanggan->nama : 'ID #' . $pelangganId;
                $existingLangganan = Langganan::where('pelanggan_id', $pelangganId)->first();

                Log::warning('Duplikat langganan terdeteksi oleh database', [
                    'pelanggan_id' => $pelangganId,
                    'existing_langganan_id' => $existingLangganan->id,
                    'nama_pelanggan' => $namaPelanggan
                ]);

                Notification::make()
                    ->warning()
                    ->title('Pelanggan Sudah Memiliki Langganan')
                    ->body("Pelanggan {$namaPelanggan} sudah memiliki langganan dengan ID #{$existingLangganan->id}. Satu pelanggan hanya boleh memiliki satu langganan.")
                    ->persistent()
                    ->actions([
                        Action::make('lihat')
                            ->label('Lihat Langganan')
                            ->url(LanggananResource::getUrl('edit', ['record' => $existingLangganan->id]))
                            ->button(),
                    ])
                    ->send();

                throw ValidationException::withMessages([
                    'pelanggan_id' => ["Pelanggan {$namaPelanggan} sudah memiliki langganan di database."],
                ]);
            }

            Log::error('Gagal membuat langganan: ' . $e->getMessage(), [
                'data' => $data,
                'trace' => $e->getTraceAsString()
            ]);

            Notification::make()
                ->danger()
                ->title('Gagal Membuat Langganan')
                ->body('Terjadi kesalahan saat membuat data langganan. Silakan coba lagi atau hubungi administrator.')
                ->send();

            throw $e;
        } catch (\Exception $e) {
            Log::error('Gagal membuat langganan: ' . $e->getMessage(), [
                'data' => $data,
                'trace' => $e->getTraceAsString()
            ]);

            Notification::make()
                ->danger()
                ->title('Gagal Membuat Langganan')
                ->body('Terjadi kesalahan saat membuat data langganan. Silakan coba lagi atau hubungi administrator.')
                ->send();

            throw $e;
        }
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->color('success')
            ->title('🎉 Data Berlangganan Baru Telah Ditambahkan!')
            ->body('Data Berlangganan baru telah dibuat. Klik tombol di bawah untuk melihat detailnya.')
            ->actions([
                Action::make('Lihat Data')
                    ->url($this->getResource()::getUrl('edit', ['record' => $this->record]))
                    ->button(),
            ])
            ->send();
    }

    protected function halt($message = null): void
    {
        throw ValidationException::withMessages(['error' => $message ?? 'Operasi dibatalkan karena validasi gagal.']);
    }
}