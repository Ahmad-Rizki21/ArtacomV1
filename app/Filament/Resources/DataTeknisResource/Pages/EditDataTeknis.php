<?php

namespace App\Filament\Resources\DataTeknisResource\Pages;

use App\Filament\Resources\DataTeknisResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDataTeknis extends EditRecord
{
    protected static string $resource = DataTeknisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
