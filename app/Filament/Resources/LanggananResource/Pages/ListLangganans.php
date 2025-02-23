<?php

namespace App\Filament\Resources\LanggananResource\Pages;

use App\Filament\Resources\LanggananResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLangganans extends ListRecords
{
    protected static string $resource = LanggananResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
