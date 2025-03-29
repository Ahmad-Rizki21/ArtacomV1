<?php

namespace App\Filament\Widgets;

use App\Models\Pelanggan;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget as BaseWidget;

class PelangganList extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 6;

    public function table(Table $table): Table 
    {
        return $table
            ->query(Pelanggan::query())
            ->columns([
                TextColumn::make('id')
                    ->label('No.')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nama')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('alamat')
                    ->label('Alamat')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('alamat_2')
                    ->label('Alamat 2')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('no_telp')
                    ->label('No HP')
                    ->searchable()
                    ->sortable(),
            ])
            ->paginated([5, 10, 25, 50, 100]) // Correct way to set pagination in Filament
            ->striped();
    }
}