<?php

namespace App\Filament\Resources\ServerMonitoringResource\Pages;

use App\Filament\Resources\ServerMonitoringResource;
use App\Models\ServerMetric;
use App\Jobs\MonitorMikrotikServers;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class ViewServerMonitoring extends ViewRecord
{
    protected static string $resource = ServerMonitoringResource::class;
    
    // Override default view
    protected static string $view = 'filament.resources.server-monitoring-resource.pages.view-server-monitoring';

    // Tambahkan public property untuk menyimpan data metrik
    public $latestMetric;
    
    // Metode ini dipanggil saat halaman dimuat
    public function mount($record): void
    {
        parent::mount($record);
        
        // Langsung ambil metrik terbaru dan simpan ke property publik
        $this->latestMetric = ServerMetric::where('mikrotik_server_id', $this->record->id)
            ->latest()
            ->first();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh Data')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    MonitorMikrotikServers::dispatch($this->record);
                    
                    Notification::make()
                        ->title('Monitoring job dispatched')
                        ->success()
                        ->send();
                    
                    // Setelah refresh, kita perlu me-reload halaman
                    $this->redirect(static::getUrl(['record' => $this->record]));
                }),
        ];
    }
}