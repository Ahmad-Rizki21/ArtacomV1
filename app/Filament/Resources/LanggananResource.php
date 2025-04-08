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
    protected static ?string $navigationGroup = 'FTTH';
    protected static ?int $navigationSort = -2;
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
                                                // Reset nilai total jika memilih manual
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
                                        ->dehydrated(true) // Pastikan ini selalu true
                                        ->afterStateHydrated(function ($state, callable $set, callable $get) {
                                            // Pastikan nilai tidak diresett saat form diload ulang
                                            if ($get('metode_pembayaran') == 'manual' && empty($state)) {
                                                // Jangan set nilai apapun, biarkan kosong
                                            } else if ($get('metode_pembayaran') == 'otomatis') {
                                                self::updateTotalHarga($set, $get);
                                            }
                                        })
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
    // public static function updateTotalHarga(callable $set, callable $get)
    // {
    //     try {
    //         $metodePembayaran = $get('metode_pembayaran');
            
    //         // Jika memilih otomatis, hitung otomatis harga berdasarkan layanan dan brand
    //         if ($metodePembayaran == 'otomatis') {
    //             $brandId = $get('id_brand');
    //             $layanan = $get('layanan');
    
    //             if ($brandId && $layanan) {
    //                 $hargaLayanan = HargaLayanan::findOrFail($brandId);
    
    //                 // Mendapatkan harga dasar berdasarkan layanan yang dipilih
    //                 $harga = match ($layanan) {
    //                     '10 Mbps' => $hargaLayanan->harga_10mbps,
    //                     '20 Mbps' => $hargaLayanan->harga_20mbps,
    //                     '30 Mbps' => $hargaLayanan->harga_30mbps,
    //                     '50 Mbps' => $hargaLayanan->harga_50mbps,
    //                     default => 0,
    //                 };
    
    //                 // Menghitung pajak
    //                 $pajak = ($hargaLayanan->pajak / 100) * $harga;
    //                 $totalHarga = $harga + $pajak;
    
    //                 // Debug - log nilai yang dihitung
    //                 Log::info('Hitung Total Harga di Form', [
    //                     'brand_id' => $brandId,
    //                     'layanan' => $layanan, 
    //                     'harga_dasar' => $harga,
    //                     'pajak' => $pajak,
    //                     'total_harga' => $totalHarga
    //                 ]);
    
    //                 // Set total harga jika memilih otomatis
    //                 $set('total_harga_layanan_x_pajak', $totalHarga);
    //             } else {
    //                 // Log jika brand atau layanan belum dipilih
    //                 Log::warning('Brand atau layanan belum dipilih', [
    //                     'brand_id' => $brandId,
    //                     'layanan' => $layanan
    //                 ]);
    //             }
    //         }
    //     } catch (\Exception $e) {
    //         Log::error('Gagal menghitung total harga', [
    //             'error' => $e->getMessage(),
    //             'metode_pembayaran' => $get('metode_pembayaran')
    //         ]);
    //     }
    // }

    // public static function updateTotalHarga(callable $set, callable $get)
    // {
    //     try {
    //         $metodePembayaran = $get('metode_pembayaran');
            
    //         if ($metodePembayaran == 'otomatis') {
    //             $brandId = $get('id_brand');
    //             $layanan = $get('layanan');
    
    //             if ($brandId && $layanan) {
    //                 $hargaLayanan = HargaLayanan::findOrFail($brandId);
    
    //                 // Penanganan khusus untuk Jelantik Nagrak (ajn-03)
    //                 if ($brandId === 'ajn-03') {
    //                     // Gunakan harga dari Jakinet (ajn-01)
    //                     $jakinetHarga = HargaLayanan::where('id_brand', 'ajn-01')->first();
                        
    //                     if ($jakinetHarga) {
    //                         $harga = match ($layanan) {
    //                             '10 Mbps' => $jakinetHarga->harga_10mbps,
    //                             '20 Mbps' => $jakinetHarga->harga_20mbps,
    //                             '30 Mbps' => $jakinetHarga->harga_30mbps,
    //                             '50 Mbps' => $jakinetHarga->harga_50mbps,
    //                             default => 0,
    //                         };
                            
    //                         $pajak = ($hargaLayanan->pajak / 100) * $harga;
    //                         $total = $harga + $pajak;
    //                         $totalBulat = ceil($total / 1000) * 1000;
                            
    //                         Log::info('Hitung Harga Jelantik Nagrak (menggunakan harga Jakinet)', [
    //                             'brand_id' => $brandId,
    //                             'layanan' => $layanan, 
    //                             'harga_dasar' => $harga,
    //                             'pajak' => $pajak,
    //                             'total_bulat' => $totalBulat
    //                         ]);
                            
    //                         $set('total_harga_layanan_x_pajak', $totalBulat);
    //                         return;
    //                     }
    //                 }
    
    //                 // Perhitungan normal untuk brand lain
    //                 $harga = match ($layanan) {
    //                     '10 Mbps' => $hargaLayanan->harga_10mbps,
    //                     '20 Mbps' => $hargaLayanan->harga_20mbps,
    //                     '30 Mbps' => $hargaLayanan->harga_30mbps,
    //                     '50 Mbps' => $hargaLayanan->harga_50mbps,
    //                     default => 0,
    //                 };
    
    //                 $pajak = ($hargaLayanan->pajak / 100) * $harga;
    //                 $total = $harga + $pajak;
    //                 $totalBulat = ceil($total / 1000) * 1000;
    
    //                 Log::info('Hitung Total Harga di Form', [
    //                     'brand_id' => $brandId,
    //                     'layanan' => $layanan, 
    //                     'harga_dasar' => $harga,
    //                     'pajak' => $pajak,
    //                     'total_bulat' => $totalBulat
    //                 ]);
    
    //                 $set('total_harga_layanan_x_pajak', $totalBulat);
    //             } else {
    //                 Log::warning('Brand atau layanan belum dipilih');
    //             }
    //         }
    //     } catch (\Exception $e) {
    //         Log::error('Gagal menghitung total harga', [
    //             'error' => $e->getMessage()
    //         ]);
    //     }
    // }




    public static function updateTotalHarga(callable $set, callable $get)
{
    try {
        $metodePembayaran = $get('metode_pembayaran');
        
        if ($metodePembayaran == 'otomatis') {
            $brandId = $get('id_brand');
            $layanan = $get('layanan');

            if ($brandId && $layanan) {
                $hargaLayanan = HargaLayanan::where('id_brand', $brandId)->first();
                
                if (!$hargaLayanan) {
                    Log::error('Brand layanan tidak ditemukan', ['id_brand' => $brandId]);
                    return;
                }

                // Mendapatkan harga dasar sesuai paket yang dipilih
                $harga = match ($layanan) {
                    '10 Mbps' => $hargaLayanan->harga_10mbps,
                    '20 Mbps' => $hargaLayanan->harga_20mbps, 
                    '30 Mbps' => $hargaLayanan->harga_30mbps,
                    '50 Mbps' => $hargaLayanan->harga_50mbps,
                    default => 0,
                };

                // Menghitung pajak dengan floor untuk menghindari angka berkoma
                $pajak = floor(($hargaLayanan->pajak / 100) * $harga);
                
                // Hitung total tanpa langsung membulatkan
                $total = $harga + $pajak;
                
                // Bulatkan ke atas ke kelipatan 1000
                $totalBulat = ceil($total / 1000) * 1000;
                
                // Untuk harga Jakinet, bulatkan ke nilai khusus
                if ($brandId === 'ajn-01') {
                    if ($layanan === '10 Mbps') $totalBulat = 150000;
                    else if ($layanan === '20 Mbps') $totalBulat = 220890;
                    else if ($layanan === '30 Mbps') $totalBulat = 248640; 
                    else if ($layanan === '50 Mbps') $totalBulat = 281940;
                }
                
                // Untuk harga Jelantik, bulatkan ke nilai khusus
                if ($brandId === 'ajn-02') {
                    if ($layanan === '10 Mbps') $totalBulat = 166500;
                    else if ($layanan === '20 Mbps') $totalBulat = 231990;
                    else if ($layanan === '30 Mbps') $totalBulat = 276390;
                    else if ($layanan === '50 Mbps') $totalBulat = 321789;
                }

                if ($brandId === 'ajn-03') {
                    if ($layanan === '10 Mbps') $totalBulat = 150000;
                    else if ($layanan === '20 Mbps') $totalBulat = 220890;
                    else if ($layanan === '30 Mbps') $totalBulat = 248640; 
                    else if ($layanan === '50 Mbps') $totalBulat = 281940;
                }

                Log::info('Hitung Total Harga di Form', [
                    'brand_id' => $brandId,
                    'layanan' => $layanan, 
                    'harga_dasar' => $harga,
                    'pajak_persen' => $hargaLayanan->pajak . '%',
                    'pajak_nilai' => $pajak,
                    'total_sebelum_pembulatan' => $total,
                    'total_sesudah_pembulatan' => $totalBulat
                ]);

                $set('total_harga_layanan_x_pajak', $totalBulat);
            } else {
                Log::warning('Brand atau layanan belum dipilih');
            }
        }
    } catch (\Exception $e) {
        Log::error('Gagal menghitung total harga', [
            'error' => $e->getMessage()
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
                    ->formatStateUsing(fn ($state) => 'IDR ' . number_format((int)$state, 0, ',', '.'))
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


    /**
     * Get the badge to display in the navigation.
     * 
     * @return string|null
     */
    public static function getNavigationBadge(): ?string
    {
        // Menghitung jumlah langganan yang statusnya "Suspend"
        return static::getModel()::where('user_status', 'Suspend')->count();
    }

    /**
     * Get the color of the badge to display in the navigation.
     * 
     * @return string|null
     */
    public static function getNavigationBadgeColor(): ?string
    {
        // Hitung jumlah langganan yang suspended
        $suspendedCount = static::getModel()::where('user_status', 'Suspend')->count();
        
        // Logika warna berdasarkan jumlah langganan suspended
        if ($suspendedCount === 0) {
            return 'success'; // Hijau jika tidak ada yang suspended
        } elseif ($suspendedCount < 5) {
            return 'warning'; // Kuning jika jumlah suspended < 5
        } elseif ($suspendedCount < 10) {
            return 'danger'; // Merah jika jumlah suspended 5-9
        } else {
            return 'primary'; // Biru jika jumlah suspended >= 10
        }
    }

    /**
     * Get the tooltip for the badge in the navigation.
     * 
     * @return string|null
     */
    public static function getNavigationBadgeTooltip(): ?string
    {
        // Hitung langganan suspended dan aktif
        $suspendedCount = static::getModel()::where('user_status', 'Suspend')->count();
        $activeCount = static::getModel()::where('user_status', 'Aktif')->count();
        $totalCount = static::getModel()::count();
        
        if ($suspendedCount === 0) {
            return 'Semua langganan aktif';
        } else {
            return "{$activeCount} langganan aktif, {$suspendedCount} suspended dari total {$totalCount}";
        }
    }


}

