<?php

namespace App\Filament\Resources\ServerMonitoringResource\Pages;

use App\Filament\Resources\ServerMonitoringResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListServerMonitorings extends ListRecords
{
    protected static string $resource = ServerMonitoringResource::class;

    protected function getHeaderActions(): array
    {
        return [
            
        ];
    }
}
