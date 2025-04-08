<?php

namespace App\Filament\Resources\ServerStatusResource\Pages;

use App\Filament\Resources\ServerStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewServerStatus extends ViewRecord
{
    protected static string $resource = ServerStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Back')
                ->url(static::getResource()::getUrl()),
        ];
    }
}