<?php

namespace App\Filament\Resources\HargaLayananResource\Pages;

use App\Filament\Resources\HargaLayananResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHargaLayanans extends ListRecords
{
    protected static string $resource = HargaLayananResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
