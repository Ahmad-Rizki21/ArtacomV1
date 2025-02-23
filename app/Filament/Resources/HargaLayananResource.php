<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HargaLayananResource\Pages;
use App\Models\HargaLayanan;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;

class HargaLayananResource extends Resource
{
    protected static ?string $model = HargaLayanan::class;

    protected static ?string $navigationLabel = 'Harga Layanan';
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar'; // Ikon yang sesuai
    protected static ?string $navigationGroup = 'FTTH'; // Mengelompokkan dalam grup "FTTH"

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                TextInput::make('id_brand')
                    ->label('Kode Brand')
                    ->required(),

                TextInput::make('brand')
                    ->label('Nama Brand')
                    ->required(),

                TextInput::make('pajak')
                    ->label('Pajak (%)')
                    ->required()
                    ->numeric()
                    ->suffix('%'),

                TextInput::make('harga_10mbps')
                    ->label('Harga 10 Mbps')
                    ->required()
                    ->numeric()
                    ->prefix('Rp')
                    ->helperText('Contoh: Rp 150.000'),

                TextInput::make('harga_20mbps')
                    ->label('Harga 20 Mbps')
                    ->required()
                    ->numeric()
                    ->prefix('Rp')
                    ->helperText('Contoh: Rp 200.000'),

                TextInput::make('harga_30mbps')
                    ->label('Harga 30 Mbps')
                    ->required()
                    ->numeric()
                    ->prefix('Rp')
                    ->helperText('Contoh: Rp 250.000'),

                TextInput::make('harga_50mbps')
                    ->label('Harga 50 Mbps')
                    ->required()
                    ->numeric()
                    ->prefix('Rp')
                    ->helperText('Contoh: Rp 300.000'),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('id_brand')
                    ->label('Kode Brand')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('brand')
                    ->label('Nama Brand')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('harga_10mbps')
                    ->label('Harga 10 Mbps')
                    ->money('IDR') // Format sebagai mata uang IDR (Rupiah)
                    ->sortable(),

                TextColumn::make('harga_20mbps')
                    ->label('Harga 20 Mbps')
                    ->money('IDR') // Format sebagai mata uang IDR (Rupiah)
                    ->sortable(),

                TextColumn::make('harga_30mbps')
                    ->label('Harga 30 Mbps')
                    ->money('IDR') // Format sebagai mata uang IDR (Rupiah)
                    ->sortable(),

                TextColumn::make('harga_50mbps')
                    ->label('Harga 50 Mbps')
                    ->money('IDR') // Format sebagai mata uang IDR (Rupiah)
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHargaLayanans::route('/'),
            'create' => Pages\CreateHargaLayanan::route('/create'),
            'edit' => Pages\EditHargaLayanan::route('/{record}/edit'),
        ];
    }
}
