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
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Illuminate\Support\Facades\Log;

class LanggananResource extends Resource
{
    protected static ?string $model = Langganan::class;
    protected static ?string $navigationLabel = 'Langganan / Paket';
    protected static ?string $navigationIcon = 'heroicon-o-wifi';
    protected static ?string $navigationGroup = 'Layanan';
    protected static ?string $slug = 'langganan';
    protected static ?string $modelLabel = 'Langganan';
    protected static ?string $pluralModelLabel = 'Daftar Langganan';

    /**
     * Definisi formulir untuk membuat/edit langganan
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('pelanggan_id')
                    ->label('Nama Pelanggan')
                    ->relationship('pelanggan', 'nama')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->columnSpan(2),

                Select::make('id_brand')
                    ->label('Brand Layanan')
                    ->relationship('hargaLayanan', 'brand')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live(),

                Select::make('layanan')
                    ->label('Paket Layanan')
                    ->options([
                        '10 Mbps' => '10 Mbps',
                        '20 Mbps' => '20 Mbps', 
                        '30 Mbps' => '30 Mbps',
                        '50 Mbps' => '50 Mbps',
                    ])
                    ->required()
                    ->live(),

                TextInput::make('total_harga_layanan_x_pajak')
                    ->label('Total Harga (Termasuk Pajak)')
                    ->numeric()
                    ->prefix('Rp')
                    ->disabled()
                    ->dehydrated(false)
                    ->reactive()
                    ->afterStateUpdated(fn ($state, callable $set, $get) => 
                        self::updateTotalHarga($set, $get))
            ])->columns(2);
    }

    /**
     * Metode untuk menghitung total harga berdasarkan layanan dan brand
     */
    public static function updateTotalHarga(callable $set, callable $get)
    {
        try {
            $brandId = $get('id_brand');
            $layanan = $get('layanan');

            if ($brandId && $layanan) {
                $hargaLayanan = HargaLayanan::findOrFail($brandId);

                $harga = match ($layanan) {
                    '10 Mbps' => $hargaLayanan->harga_10mbps,
                    '20 Mbps' => $hargaLayanan->harga_20mbps,
                    '30 Mbps' => $hargaLayanan->harga_30mbps,
                    '50 Mbps' => $hargaLayanan->harga_50mbps,
                    default => 0,
                };

                $pajak = ($hargaLayanan->pajak / 100) * $harga;
                $totalHarga = $harga + $pajak;

                $set('total_harga_layanan_x_pajak', $totalHarga);
            }
        } catch (\Exception $e) {
            Log::error('Gagal menghitung total harga', [
                'error' => $e->getMessage(),
                'brand_id' => $get('id_brand'),
                'layanan' => $get('layanan')
            ]);
        }
    }

    /**
     * Definisi kolom tabel
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('pelanggan.nama')
                    ->label('Pelanggan')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('hargaLayanan.brand')
                    ->label('Brand')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('layanan')
                    ->label('Paket')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                TextColumn::make('total_harga_layanan_x_pajak')
                    ->label('Total Harga')
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
            ])
            ->filters([
                SelectFilter::make('id_brand')
                    ->label('Filter Brand')
                    ->relationship('hargaLayanan', 'brand')
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make()
            ])
            ->bulkActions([
                DeleteBulkAction::make()
            ])
            ->defaultSort('created_at', 'desc');
    }

    /**
     * Definisi halaman-halaman resource
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLangganans::route('/'),
            'create' => Pages\CreateLangganan::route('/create'),
            'edit' => Pages\EditLangganan::route('/{record}/edit'),
        ];
    }
}