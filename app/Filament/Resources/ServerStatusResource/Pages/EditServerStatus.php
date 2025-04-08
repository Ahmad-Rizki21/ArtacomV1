<?php

namespace App\Filament\Resources\ServerStatusResource\Pages;

use App\Filament\Resources\ServerStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditServerStatus extends EditRecord
{
    protected static string $resource = ServerStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
