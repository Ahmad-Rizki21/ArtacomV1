<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HargaLayananResource\Pages;
use App\Models\HargaLayanan;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Placeholder;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class HargaLayananResource extends Resource
{
    protected static ?string $model = HargaLayanan::class;

    protected static ?string $navigationLabel = 'Harga Layanan';
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'FTTH';
    protected static ?int $navigationSort = 2;
    protected static ?string $recordTitleAttribute = 'brand';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Section::make('Informasi Brand')
                    ->description('Identifikasi dan informasi dasar brand')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        Grid::make()
                            ->columns(2)
                            ->schema([
                                TextInput::make('id_brand')
                                    ->label('Kode Brand')
                                    ->required()
                                    ->maxLength(10)
                                    ->columnSpan(1),

                                TextInput::make('brand')
                                    ->label('Nama Brand')
                                    ->required()
                                    ->maxLength(50)
                                    ->columnSpan(1),
                            ]),
                    ]),

                Section::make('Informasi Pajak')
                    ->description('Pengaturan persentase pajak untuk layanan')
                    ->icon('heroicon-o-calculator')
                    ->schema([
                        TextInput::make('pajak')
                            ->label('Pajak (%)')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->default(11)
                            ->helperText('Persentase pajak yang akan diterapkan ke semua harga.'),
                    ]),

                Section::make('Harga Paket Internet')
                    ->description('Pengaturan harga untuk berbagai kecepatan paket internet')
                    ->icon('heroicon-o-signal')
                    ->columns(2)
                    ->schema([
                        Card::make()
                            ->schema([
                                TextInput::make('harga_10mbps')
                                    ->label('Harga 10 Mbps')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix('Rp')
                                    ->helperText('Contoh: 150000 untuk Rp 150.000'),
                            ])
                            ->columnSpan(1),

                        Card::make()
                            ->schema([
                                TextInput::make('harga_20mbps')
                                    ->label('Harga 20 Mbps')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix('Rp')
                                    ->helperText('Contoh: 200000 untuk Rp 200.000'),
                            ])
                            ->columnSpan(1),

                        Card::make()
                            ->schema([
                                TextInput::make('harga_30mbps')
                                    ->label('Harga 30 Mbps')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix('Rp')
                                    ->helperText('Contoh: 250000 untuk Rp 250.000'),
                            ])
                            ->columnSpan(1),

                        Card::make()
                            ->schema([
                                TextInput::make('harga_50mbps')
                                    ->label('Harga 50 Mbps')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix('Rp')
                                    ->helperText('Contoh: 300000 untuk Rp 300.000'),
                            ])
                            ->columnSpan(1),
                    ]),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('id_brand')
                    ->label('Kode Brand')
                    ->sortable()
                    ->searchable()
                    ->weight('bold')
                    ->copyable(),

                TextColumn::make('brand')
                    ->label('Nama Brand')
                    ->sortable()
                    ->searchable()
                    ->weight('medium')
                    ->copyable(),

                TextColumn::make('pajak')
                    ->label('Pajak')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => "{$state}%")
                    ->alignCenter(),

                TextColumn::make('harga_10mbps')
                    ->label('10 Mbps')
                    ->money('IDR')
                    ->sortable()
                    ->alignRight()
                    ->color('success'),

                TextColumn::make('harga_20mbps')
                    ->label('20 Mbps')
                    ->money('IDR')
                    ->sortable()
                    ->alignRight()
                    ->color('success'),

                TextColumn::make('harga_30mbps')
                    ->label('30 Mbps')
                    ->money('IDR')
                    ->sortable()
                    ->alignRight()
                    ->color('success'),

                TextColumn::make('harga_50mbps')
                    ->label('50 Mbps')
                    ->money('IDR')
                    ->sortable()
                    ->alignRight()
                    ->color('success'),
            ])
            ->filters([
                SelectFilter::make('pajak')
                    ->label('Filter Pajak')
                    ->options([
                        '10' => '10%',
                        '11' => '11%',
                        '12' => '12%',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'],
                            fn (Builder $query, $value): Builder => $query->where('pajak', $value),
                        );
                    }),
            ])
            ->actions([
                ViewAction::make()
                    ->icon('heroicon-s-eye')
                    ->color('info')
                    ->modalHeading(fn (HargaLayanan $record): string => "Detail Harga Layanan: {$record->brand}")
                    ->modalContent(fn (HargaLayanan $record): string => "
                        <div class='space-y-4'>
                            <div class='p-4 bg-gray-100 rounded-lg'>
                                <h3 class='font-medium'>Informasi Brand</h3>
                                <p><strong>Kode:</strong> {$record->id_brand}</p>
                                <p><strong>Nama:</strong> {$record->brand}</p>
                                <p><strong>Pajak:</strong> {$record->pajak}%</p>
                            </div>
                            <div class='p-4 bg-gray-100 rounded-lg'>
                                <h3 class='font-medium'>Harga Paket</h3>
                                <div class='grid grid-cols-2 gap-4 mt-2'>
                                    <div>
                                        <p><strong>10 Mbps:</strong> Rp " . number_format($record->harga_10mbps, 0, ',', '.') . "</p>
                                        <p><strong>20 Mbps:</strong> Rp " . number_format($record->harga_20mbps, 0, ',', '.') . "</p>
                                    </div>
                                    <div>
                                        <p><strong>30 Mbps:</strong> Rp " . number_format($record->harga_30mbps, 0, ',', '.') . "</p>
                                        <p><strong>50 Mbps:</strong> Rp " . number_format($record->harga_50mbps, 0, ',', '.') . "</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    "),

                EditAction::make()
                    ->icon('heroicon-s-pencil')
                    ->color('warning'),
                    
                DeleteAction::make()
                    ->icon('heroicon-s-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Harga Layanan')
                    ->modalDescription('Apakah Anda yakin ingin menghapus harga layanan ini? Tindakan ini tidak dapat dibatalkan.'),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->icon('heroicon-s-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Harga Layanan')
                    ->modalDescription('Apakah Anda yakin ingin menghapus semua harga layanan yang dipilih? Tindakan ini tidak dapat dibatalkan.'),
            ])
            ->defaultSort('brand', 'asc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHargaLayanans::route('/'),
            'create' => Pages\CreateHargaLayanan::route('/create'),
            'edit' => Pages\EditHargaLayanan::route('/{record}/edit'),
        ];
    }
    
    public static function getGloballySearchableAttributes(): array
    {
        return ['id_brand', 'brand'];
    }
}