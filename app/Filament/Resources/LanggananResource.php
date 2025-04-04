<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LanggananResource\Pages;
use App\Models\Langganan;
use App\Models\Pelanggan;
use App\Models\HargaLayanan;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Fieldset;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Illuminate\Support\Facades\Log;
use Filament\Tables\Actions\ViewAction;
use Carbon\Carbon;

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
                // Membuat bagian khusus untuk data pelanggan
                Section::make('Informasi Pelanggan')
                    ->description('Pilih pelanggan dan brand layanan')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Grid::make()
                            ->columns(2)
                            ->schema([
                                Select::make('pelanggan_id')
                                    ->label('Nama Pelanggan')
                                    ->relationship('pelanggan', 'nama')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        // Ambil data pelanggan
                                        $pelanggan = Pelanggan::find($state);
                                        
                                        // Set brand default dan harga jika pelanggan ditemukan
                                        if ($pelanggan) {
                                            $set('id_brand', $pelanggan->brand_default ?? null);
                                        }
                                    }),

                                Select::make('id_brand')
                                    ->label('Brand Layanan')
                                    ->options(HargaLayanan::pluck('brand', 'id_brand'))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        // Debugging untuk melihat nilai id_brand
                                        Log::info('Selected Brand:', ['id_brand' => $state]);
                                    }),
                            ]),
                    ]),

                // Bagian khusus untuk detail paket layanan
                Section::make('Detail Paket Layanan')
                    ->description('Pilih paket dan metode pembayaran')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Grid::make()
                            ->columns(2)
                            ->schema([
                                Select::make('layanan')
                                    ->label('Paket Layanan')
                                    ->options([
                                        '10 Mbps' => '10 Mbps',
                                        '20 Mbps' => '20 Mbps',
                                        '30 Mbps' => '30 Mbps',
                                        '50 Mbps' => '50 Mbps',
                                    ])
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        self::updateTotalHarga($set, $get);
                                    }),

                                DatePicker::make('tgl_jatuh_tempo')
                                    ->label('Tanggal Jatuh Tempo')
                                    ->required()
                                    ->displayFormat('d M Y')
                                    ->closeOnDateSelection(),
                            ]),

                        // Pembayaran dalam fieldset
                        Fieldset::make('Informasi Pembayaran')
                            ->schema([
                                Grid::make()
                                    ->columns(2)
                                    ->schema([
                                        Select::make('metode_pembayaran')
                                            ->label('Metode Pembayaran')
                                            ->helperText('Otomatis untuk harga reguler, Manual untuk prorate')
                                            ->options([
                                                'otomatis' => 'Otomatis (Reguler)',
                                                'manual' => 'Manual (Prorate)',
                                            ])
                                            ->required()
                                            ->live()
                                            ->default('otomatis')
                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                if ($state == 'manual') {
                                                    $set('total_harga_layanan_x_pajak', null);
                                                } else {
                                                    self::updateTotalHarga($set, $get);
                                                }
                                            }),
                                        
                                        TextInput::make('total_harga_layanan_x_pajak')
                                            ->label('Total Harga (Termasuk Pajak)')
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->required(fn ($get) => $get('metode_pembayaran') == 'manual')
                                            ->disabled(fn ($get) => $get('metode_pembayaran') == 'otomatis')
                                            ->dehydrated(true)
                                            ->helperText(fn ($get) => $get('metode_pembayaran') == 'manual' 
                                                ? 'Masukkan total harga termasuk pajak' 
                                                : 'Harga dihitung otomatis berdasarkan paket')
                                            ->placeholder('0'),
                                    ]),

                                // Informasi perhitungan harga (hanya ditampilkan saat otomatis)
                                Placeholder::make('perhitungan_info')
                                    ->label('Informasi Perhitungan')
                                    ->content(fn ($get) => $get('metode_pembayaran') == 'otomatis' 
                                        ? 'Harga telah dihitung otomatis berdasarkan paket layanan dan brand yang dipilih, sudah termasuk pajak.'
                                        : 'Masukkan total harga manual sesuai perhitungan prorate.')
                                    ->columnSpan(2),
                            ]),
                    ]),
            ]);
    }

    /**
     * Metode untuk menghitung total harga berdasarkan layanan dan brand
     */
    public static function updateTotalHarga(callable $set, callable $get)
    {
        try {
            $metodePembayaran = $get('metode_pembayaran');
            
            // Jika memilih otomatis, hitung otomatis harga berdasarkan layanan dan brand
            if ($metodePembayaran == 'otomatis') {
                $brandId = $get('id_brand');
                $layanan = $get('layanan');
    
                if ($brandId && $layanan) {
                    $hargaLayanan = HargaLayanan::findOrFail($brandId);
    
                    // Mendapatkan harga dasar berdasarkan layanan yang dipilih
                    $harga = match ($layanan) {
                        '10 Mbps' => $hargaLayanan->harga_10mbps,
                        '20 Mbps' => $hargaLayanan->harga_20mbps,
                        '30 Mbps' => $hargaLayanan->harga_30mbps,
                        '50 Mbps' => $hargaLayanan->harga_50mbps,
                        default => 0,
                    };
    
                    // Menghitung pajak
                    $pajak = ($hargaLayanan->pajak / 100) * $harga;
                    $totalHarga = $harga + $pajak;
    
                    // Debug - log nilai yang dihitung
                    Log::info('Hitung Total Harga di Form', [
                        'brand_id' => $brandId,
                        'layanan' => $layanan, 
                        'harga_dasar' => $harga,
                        'pajak' => $pajak,
                        'total_harga' => $totalHarga
                    ]);
    
                    // Set total harga jika memilih otomatis
                    $set('total_harga_layanan_x_pajak', $totalHarga);
                } else {
                    // Log jika brand atau layanan belum dipilih
                    Log::warning('Brand atau layanan belum dipilih', [
                        'brand_id' => $brandId,
                        'layanan' => $layanan
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Gagal menghitung total harga', [
                'error' => $e->getMessage(),
                'metode_pembayaran' => $get('metode_pembayaran')
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

                TextColumn::make('tgl_jatuh_tempo')
                    ->label('Tanggal Jatuh Tempo')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('tgl_invoice_terakhir')
                    ->label('Invoice Terakhir')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('user_status')
                    ->label('Status User')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Aktif' => 'success',
                        'Suspend' => 'danger',
                        'Tidak Ada Invoice' => 'warning',
                        'Kadaluarsa' => 'warning',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'Aktif' => 'heroicon-o-check-circle',
                        'Suspend' => 'heroicon-o-clock',
                        'Tidak Ada Invoice' => 'heroicon-o-pencil',
                        'Kadaluarsa' => 'heroicon-o-trash',
                    }),

                TextColumn::make('id_pelanggan')
                    ->label('ID Pelanggan')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('profile_pppoe')
                    ->label('Profile PPPoE')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('olt')
                    ->label('OLT')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

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
            ->filtersTriggerAction(
                fn (Action $action) => $action
                    ->button()
                    ->label('Filter'),
            )
            ->actions([
                EditAction::make(),
                ViewAction::make(),
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Hapus Data Berlangganan')
                    ->modalDescription('Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.')
                    ->modalSubmitActionLabel('Ya, Hapus')
                    ->modalCancelActionLabel('Batal')
                    ->successNotificationTitle('🗑️ Pelanggan Berhasil Dihapus!')
                    ->after(function () {
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('🗑️ Data Berlangganan Telah Dihapus!')
                            ->body('Pelanggan ini telah dihapus secara permanen.')
                            ->send();
                    })
            ])
            ->bulkActions([
                DeleteBulkAction::make()
            ])
            ->defaultSort('id', 'asc');
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