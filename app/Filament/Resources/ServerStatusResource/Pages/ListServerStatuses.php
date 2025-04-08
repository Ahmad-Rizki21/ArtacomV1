<?php

namespace App\Filament\Resources\ServerStatusResource\Pages;

use App\Filament\Resources\ServerStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListServerStatuses extends ListRecords
{
    protected static string $resource = ServerStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
