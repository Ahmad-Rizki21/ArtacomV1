<?php

namespace App\Filament\Resources\DataTeknisResource\Pages;

use App\Filament\Resources\DataTeknisResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\DataTeknisImport;
use Filament\Notifications\Notification;
use App\Exports\DataTeknisExport;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Log;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Checkbox;
use Filament\Support\Enums\ActionSize;
use Filament\Forms\Components\Select;

class ListDataTeknis extends ListRecords
{
    use WithFileUploads;
    
    protected static string $resource = DataTeknisResource::class;
    
    public $file;
    
    protected function getHeaderActions(): array
    {
        // Definisi lokasi untuk export
        $locations = [
            'all' => '-- Semua Data --',
            'Nagrak' => 'Rusun Nagrak',
            'Pinus Elok' => 'Rusun Pinus Elok',
            'Pulogebang Tower' => 'Rusun Pulogebang Tower',
            'Tipar Cakung' => 'Rusun Tipar Cakung',
            'Tambun' => 'Rusun Tambun',
            'Parama' => 'Perumahan Parama',
            'Waringin' => 'Perumahan Waringin'
        ];

        return [
            CreateAction::make()
                ->label('Tambah Data Teknis')
                ->icon('heroicon-o-plus-circle'),

            Action::make('import')
                ->label('Import Excel/CSV')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->size(ActionSize::Large)
                ->modalWidth('lg')
                ->modalHeading('Import Data Teknis')
                ->modalDescription('Upload file Excel atau CSV yang berisi data teknis. Pastikan format file sesuai dengan template.')
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
                        ->url(route('data-teknis.template.download'))
                        ->openUrlInNewTab()
                        ->visible(true)
                ])
                ->action(function (array $data) {
                    try {
                        $path = Storage::disk('local')->path($data['file']);
                        
                        Excel::import(
                            new DataTeknisImport(),
                            $path
                        );
                        
                        Notification::make()
                            ->title('Import Berhasil')
                            ->body('Data teknis telah berhasil diimpor.')
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
                ->modalHeading('Export Data Teknis')
                ->modalDescription('Pilih lokasi untuk mengekspor data teknis.')
                ->form([
                    Select::make('location')
                        ->label('Lokasi')
                        ->options($locations)
                        ->searchable()
                        ->required()
                        ->default('all')
                ])
                ->action(function (array $data) {
                    $location = $data['location'] ?? 'all';
                    $filename = 'data_teknis' . ($location !== 'all' ? "-{$location}" : '-semua') . '.xlsx';
                    
                    return Excel::download(new DataTeknisExport($location), $filename);
                })
        ];
    }
}