<?php

namespace App\Filament\Resources\LanggananResource\Pages;

use App\Filament\Resources\LanggananResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLangganan extends EditRecord
{
    protected static string $resource = LanggananResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
