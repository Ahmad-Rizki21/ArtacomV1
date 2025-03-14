<?php

namespace App\Filament\Resources\IsolirResource\Pages;

use App\Filament\Resources\IsolirResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditIsolir extends EditRecord
{
    protected static string $resource = IsolirResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
