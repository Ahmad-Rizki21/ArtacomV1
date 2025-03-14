<?php

namespace App\Filament\Resources\MikrotikServerResource\Pages;

use App\Filament\Resources\MikrotikServerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMikrotikServer extends EditRecord
{
    protected static string $resource = MikrotikServerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
