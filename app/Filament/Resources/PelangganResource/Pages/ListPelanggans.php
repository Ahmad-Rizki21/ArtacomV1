<?php

namespace App\Filament\Resources\PelangganResource\Pages;

use App\Filament\Resources\PelangganResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\PelangganImport;
use Filament\Notifications\Notification;
use App\Exports\PelangganExport;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Log;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Checkbox;
use Filament\Support\Enums\ActionSize;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\DB;

class ListPelanggans extends ListRecords
{
    use WithFileUploads;
    
    protected static string $resource = PelangganResource::class;
    
    public $file;
    
    protected function getHeaderActions(): array
    {

        // Dapatkan semua lokasi unik dari database
        $locations = DB::table('pelanggan')
            ->select('alamat')
            ->whereNotNull('alamat')
            ->distinct()
            ->get()
            ->pluck('alamat', 'alamat')
            ->toArray();
            
        // Tambahkan lokasi manual jika belum ada
        $manualLocations = [
            'Rusun Nagrak' => 'Rusun Nagrak',
            'Rusun Pinus Elok' => 'Rusun Pinus Elok',
            'Rusun Pulogebang Tower' => 'Rusun Pulogebang Tower',
            'Rusun KM2' => 'Rusun KM2',
            'Rusun Tipar Cakung' => 'Rusun Tipar Cakung',
            'Rusun Albo' => 'Rusun Albo',
            'Perumahan Tambun' => 'Perumahan Tambun',
            'Perumahan Waringin Kurung' => 'Perumahan Waringin Kurung',
            'Perumahan Parama Serang' => 'Perumahan Parama Serang',
        ];
        
        // Gabungkan lokasi dari database dan manual, hilangkan duplikat
        $allLocations = array_merge(['' => '-- Semua Lokasi --'], $locations, $manualLocations);


        return [
            CreateAction::make()
            ->label('Tambah Pelanggan')
            ->icon('heroicon-o-plus-circle'),

            Action::make('import')
                ->label('Import Excel/CSV')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->size(ActionSize::Large)
                ->modalWidth('lg')
                ->modalHeading('Import Data Pelanggan')
                ->modalDescription('Upload file Excel atau CSV yang berisi data pelanggan. Pastikan format file sesuai dengan template.')
                ->form([
                    FileUpload::make('file')
                        ->label('File Excel/CSV')
                        ->disk('local')
                        ->directory('imports')
                        ->acceptedFileTypes(['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv'])
                        ->maxSize(5120) // 5MB
                        ->required(),
                        
                    Checkbox::make('has_header_row')
                        ->label('File memiliki baris header')
                        ->default(true)
                        ->helperText('Centang jika baris pertama pada file adalah judul kolom'),
                ])
                ->extraModalFooterActions([
                    Action::make('downloadTemplate')
                        ->label('Download Template')
                        ->button()
                        ->outlined()
                        ->color('secondary')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->url(route('pelanggan.template.download'))
                        ->openUrlInNewTab()
                        ->visible(true)
                ])
                ->action(function (array $data) {
                    try {
                        $path = Storage::disk('local')->path($data['file']);
                        
                        Excel::import(
                            new PelangganImport(),
                            $path
                        );
                        
                        Notification::make()
                            ->title('Import Berhasil')
                            ->body('Data pelanggan telah berhasil diimpor.')
                            ->success()
                            ->send();
                            
                        // Hapus file setelah diproses
                        Storage::disk('local')->delete($data['file']);
                        
                    } catch (\Exception $e) {
                        Log::error('Import error', [
                            'message' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        
                        Notification::make()
                            ->title('Import Gagal')
                            ->body('Terjadi kesalahan: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
            }),
            
            // Tambahkan action export
            Action::make('export')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->modalWidth('md')
                ->modalHeading('Export Data Pelanggan')
                ->modalDescription('Pilih lokasi untuk mengekspor data pelanggan tertentu, atau pilih "Semua Lokasi" untuk mengekspor semua data.')
                ->form([
                    Select::make('location')
                        ->label('Lokasi')
                        ->options($allLocations)
                        ->searchable()
                ])
                ->action(function (array $data) {
                    $location = $data['location'] ?? null;
                    $filename = 'pelanggan' . ($location ? "-{$location}" : '-semua') . '.xlsx';
                    
                    return Excel::download(new PelangganExport($location), $filename);
                })
        ];
    }
}