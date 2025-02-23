<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LanggananResource\Pages;
use App\Models\Langganan;
use App\Models\Pelanggan;
use App\Models\HargaLayanan;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;

class LanggananResource extends Resource
{
    protected static ?string $model = Langganan::class;

    protected static ?string $navigationLabel = 'Langganan / Paket';
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'FTTH'; // Mengelompokkan dalam grup "FTTH"

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('pelanggan_id')
                    ->label('Pelanggan')
                    ->options(Pelanggan::pluck('nama', 'id'))
                    ->searchable()
                    ->required(),

                Select::make('id_brand')
                    ->label('Brand')
                    ->options(HargaLayanan::pluck('brand', 'id_brand'))
                    ->searchable()
                    ->live()
                    ->required(),

                Select::make('layanan')
                    ->label('Layanan')
                    ->options([
                        '10 Mbps' => '10 Mbps',
                        '20 Mbps' => '20 Mbps',
                        '30 Mbps' => '30 Mbps',
                        '50 Mbps' => '50 Mbps',
                    ])
                    ->live()
                    ->required(),

                TextInput::make('total_harga_layanan_x_pajak')
                    ->label('Total Harga (Pajak)')
                    ->numeric()
                    ->default(0)
                    ->disabled()
                    ->dehydrated(false)
                    ->reactive()
                    ->afterStateUpdated(fn ($state, callable $set, $get) => self::updateTotalHarga($set, $get)),
            ]);
    }

    public static function updateTotalHarga(callable $set, callable $get)
    {
        $brandId = $get('id_brand');
        $layanan = $get('layanan');

        if ($brandId && $layanan) {
            // Ambil harga layanan berdasarkan Brand
            $hargaLayanan = HargaLayanan::find($brandId);

            if ($hargaLayanan) {
                // Ambil harga sesuai layanan
                $harga = match ($layanan) {
                    '10 Mbps' => $hargaLayanan->harga_10mbps,
                    '20 Mbps' => $hargaLayanan->harga_20mbps,
                    '30 Mbps' => $hargaLayanan->harga_30mbps,
                    '50 Mbps' => $hargaLayanan->harga_50mbps,
                    default => 0,
                };

                // Hitung harga setelah pajak
                $pajak = ($hargaLayanan->pajak / 100) * $harga;
                $totalHarga = $harga + $pajak;

                // Set total harga ke field
                $set('total_harga_layanan_x_pajak', $totalHarga);
            }
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('pelanggan.nama')
                    ->label('Pelanggan')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('hargaLayanan.brand')
                    ->label('Brand')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('layanan')
                    ->label('Layanan')
                    ->sortable(),

                TextColumn::make('total_harga_layanan_x_pajak')
                    ->label('Total Harga (Pajak)')
                    ->sortable()
                    ->money('IDR'),

                TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime(),
            ])
            ->filters([
                SelectFilter::make('id_brand')
                    ->label('Filter Brand')
                    ->relationship('hargaLayanan', 'brand')
                    ->searchable(),
            ])
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
            'index' => Pages\ListLangganans::route('/'),
            'create' => Pages\CreateLangganan::route('/create'),
            'edit' => Pages\EditLangganan::route('/{record}/edit'),
        ];
    }
}
