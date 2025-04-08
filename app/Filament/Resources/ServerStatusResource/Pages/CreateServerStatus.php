<?php

namespace App\Filament\Resources\ServerStatusResource\Pages;

use App\Filament\Resources\ServerStatusResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateServerStatus extends CreateRecord
{
    protected static string $resource = ServerStatusResource::class;

    // Redirect ke halaman list jika diakses
    public function mount(): void
    {
        redirect(static::getResource()::getUrl());
    }
}