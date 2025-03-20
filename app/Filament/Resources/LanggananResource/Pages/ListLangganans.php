<?php

namespace App\Filament\Resources\LanggananResource\Pages;

use App\Filament\Resources\LanggananResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\LanggananImport;
use Filament\Notifications\Notification;
use App\Exports\LanggananExport;
use App\Exports\LanggananTemplateExport;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Log;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Checkbox;
use Filament\Support\Enums\ActionSize;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\DB;

class ListLangganans extends ListRecords
{
    use WithFileUploads;
    
    protected static string $resource = LanggananResource::class;
    
    public $file;
    
    // Mapping brand untuk filter
    protected $brandMapping = [
        'Jakinet' => 'Jakinet',
        'Jelantik' => 'Jelantik',
        'Jelantik Nagrak' => 'Jelantik Nagrak'
    ];
    
    protected function getHeaderActions(): array
    {
        // Dapatkan brand unik dari database
        $brands = DB::table('langganan')
            ->select('id_brand')
            ->whereNotNull('id_brand')
            ->distinct()
            ->get()
            ->pluck('id_brand', 'id_brand')
            ->toArray();
        
        // Filter brand sesuai mapping
        $filteredBrands = array_intersect_key(
            array_merge(
                ['all' => '-- Semua Brand --'], 
                $this->brandMapping
            ), 
            array_flip(array_merge(
                ['all'], 
                array_keys($this->brandMapping)
            ))
        );

        // Opsi status
        $statusOptions = [
            'all' => '-- Semua Status --',
            'aktif' => 'Aktif',
            'suspend' => 'Suspend'
        ];

        return [
            CreateAction::make()
                ->label('Tambah Langganan')
                ->icon('heroicon-o-plus-circle'),

            Action::make('import')
                ->label('Import Excel/CSV')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->size(ActionSize::Large)
                ->modalWidth('lg')
                ->modalHeading('Import Data Langganan')
                ->modalDescription('Upload file Excel atau CSV yang berisi data langganan. Pastikan format file sesuai dengan template.')
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
                        ->url(route('langganan.template.download'))
                        ->openUrlInNewTab()
                        ->visible(true)
                ])
                ->action(function (array $data) {
                    try {
                        $path = Storage::disk('local')->path($data['file']);
                        
                        Excel::import(
                            new LanggananImport(),
                            $path
                        );
                        
                        Notification::make()
                            ->title('Import Berhasil')
                            ->body('Data langganan telah berhasil diimpor.')
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
                ->modalHeading('Export Data Langganan')
                ->modalDescription('Pilih brand dan status untuk mengekspor data langganan.')
                ->form([
                    Select::make('brand')
                        ->label('Brand')
                        ->options($filteredBrands)
                        ->searchable()
                        ->default('all'),
                    
                    Select::make('status')
                        ->label('Status')
                        ->options($statusOptions)
                        ->searchable()
                        ->default('all')
                ])
                ->action(function (array $data) {
                    $brand = $data['brand'] ?? 'all';
                    $status = $data['status'] ?? 'all';
                    
                    $filename = 'langganan';
                    if ($brand !== 'all') $filename .= "-{$brand}";
                    if ($status !== 'all') $filename .= "-{$status}";
                    $filename .= '.xlsx';
                    
                    return Excel::download(
                        new LanggananExport($brand, $status), 
                        $filename
                    );
                })
        ];
    }
}