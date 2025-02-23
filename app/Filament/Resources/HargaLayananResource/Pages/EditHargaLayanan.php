<?php

namespace App\Filament\Resources\HargaLayananResource\Pages;

use App\Filament\Resources\HargaLayananResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHargaLayanan extends EditRecord
{
    protected static string $resource = HargaLayananResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
