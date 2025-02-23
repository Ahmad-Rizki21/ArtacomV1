<?php

namespace App\Filament\Resources\DataTeknisResource\Pages;

use App\Filament\Resources\DataTeknisResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDataTeknis extends ListRecords
{
    protected static string $resource = DataTeknisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
