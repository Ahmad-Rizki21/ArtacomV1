<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LanggananResource\Pages;
use App\Models\Langganan;
use App\Models\Pelanggan;
use App\Models\HargaLayanan;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Fieldset;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ViewAction;
use Carbon\Carbon;
use Illuminate\Support\HtmlString;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action as NotificationAction;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Filament\Tables\Actions\Action;

class LanggananResource extends Resource
{
    protected static ?string $model = Langganan::class;
    protected static ?string $navigationLabel = 'Langganan / Paket';
    protected static ?string $navigationIcon = 'heroicon-o-wifi';
    protected static ?string $navigationGroup = 'FTTH';
    protected static ?int $navigationSort = -3;
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
                            // Nonaktifkan komponen saat mode edit
                            ->disabled(function ($livewire) {
                                // Jika ini adalah halaman edit, disable
                                return $livewire instanceof \App\Filament\Resources\LanggananResource\Pages\EditLangganan;
                            })
                            // Pastikan tetap di-save meskipun disabled
                            ->dehydrated(true)
                            // Validasi hanya saat create, tidak saat edit
                            ->rules([
                                function ($get, $context) {
                                    // Hanya lakukan validasi saat create
                                    if ($context === 'create') {
                                        return function (string $attribute, $value, $fail) {
                                            if (!$value) {
                                                $fail('Pelanggan harus dipilih.');
                                                return;
                                            }

                                            $query = Langganan::where('pelanggan_id', $value)
                                                        ->where('user_status', 'Aktif');

                                            if ($query->exists()) {
                                                $existingLangganan = $query->first();
                                                $pelanggan = Pelanggan::find($value);
                                                $namaPelanggan = $pelanggan ? $pelanggan->nama : 'ID #' . $value;
                                                $fail("Pelanggan {$namaPelanggan} sudah memiliki langganan aktif dengan ID #{$existingLangganan->id}. Satu pelanggan hanya boleh memiliki satu langganan aktif.");
                                            }
                                        };
                                    }
                                    
                                    return null;
                                }
                            ])
                            ->afterStateUpdated(function ($state, callable $set, callable $get, $livewire) {
                                if (!$state) return;

                                // Isi data otomatis tanpa validasi duplikat
                                $pelanggan = Pelanggan::find($state);
                                if ($pelanggan) {
                                    if (!empty($pelanggan->id_brand)) {
                                        $set('id_brand', $pelanggan->id_brand);
                                        $brand = HargaLayanan::where('id_brand', $pelanggan->id_brand)->first();
                                        if ($brand) {
                                            $set('brand_display', $brand->brand);
                                        }
                                    }

                                    if (!empty($pelanggan->layanan)) {
                                        $set('layanan', $pelanggan->layanan);
                                        $set('layanan_display', $pelanggan->layanan);
                                    }

                                    if (!empty($pelanggan->id_brand) && !empty($pelanggan->layanan)) {
                                        self::updateTotalHarga($set, $get);
                                    }
                                }
                            }),

                                TextInput::make('brand_display')
                                    ->label('Brand Layanan')
                                    ->placeholder('Brand layanan akan otomatis terisi dari data pelanggan')
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),

                        TextInput::make('id_brand')
                            ->hidden()
                            ->dehydrated(),

                        Placeholder::make('informasi_pelanggan')
                            ->content(function (callable $get) {
                                $pelangganId = $get('pelanggan_id');
                                if (!$pelangganId) return 'Pilih pelanggan terlebih dahulu';

                                $pelanggan = Pelanggan::find($pelangganId);
                                if (!$pelanggan) return 'Pelanggan tidak ditemukan';

                                $info = "<div class='text-sm space-y-1 mt-2'>";
                                $info .= "<div><strong>Alamat:</strong> " . ($pelanggan->alamat == 'Lainnya' ? $pelanggan->alamat_custom : $pelanggan->alamat) . "</div>";
                                $info .= "<div><strong>Blok/Unit:</strong> " . $pelanggan->blok . "/" . $pelanggan->unit . "</div>";
                                $info .= "<div><strong>Kontak:</strong> " . $pelanggan->no_telp . " | " . $pelanggan->email . "</div>";

                                if ($pelanggan->tgl_instalasi) {
                                    $info .= "<div><strong>Tanggal Instalasi:</strong> " . Carbon::parse($pelanggan->tgl_instalasi)->format('d M Y') . "</div>";
                                }

                                if ($pelanggan->id_brand || $pelanggan->layanan) {
                                    $info .= "<div class='mt-2 p-2 bg-blue-50 rounded-md border border-blue-200'>";
                                    $info .= "<div class='font-medium text-blue-700'>Data Default Layanan:</div>";
                                    $brandName = HargaLayanan::where('id_brand', $pelanggan->id_brand)->first()?->brand ?? $pelanggan->id_brand;
                                    $info .= "<div><strong>Brand Default:</strong> " . $brandName . "</div>";
                                    $info .= "<div><strong>Paket Default:</strong> " . ($pelanggan->layanan ?? 'Belum diatur') . "</div>";
                                    $info .= "</div>";
                                }

                                $info .= "</div>";

                                return new HtmlString($info);
                            })
                            ->columnSpan('full'),
                    ]),

                Section::make('Detail Paket Layanan')
                    ->description('Pilih paket dan metode pembayaran')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Grid::make()
                            ->columns(2)
                            ->schema([
                                TextInput::make('layanan_display')
                                    ->label('Paket Layanan')
                                    ->placeholder('Paket layanan akan otomatis terisi dari data pelanggan')
                                    ->disabled()
                                    ->dehydrated(false),

                                TextInput::make('layanan')
                                    ->hidden()
                                    ->dehydrated(),

                                DatePicker::make('tgl_jatuh_tempo')
                                    ->label('Tanggal Jatuh Tempo')
                                    ->required()
                                    ->displayFormat('d M Y')
                                    ->closeOnDateSelection(),
                            ]),

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
                                            ->afterStateHydrated(function ($state, callable $set, callable $get) {
                                                if ($get('metode_pembayaran') == 'otomatis') {
                                                    self::updateTotalHarga($set, $get);
                                                }
                                            })
                                            ->helperText(fn ($get) => $get('metode_pembayaran') == 'manual' 
                                                ? 'Masukkan total harga termasuk pajak' 
                                                : 'Harga dihitung otomatis berdasarkan paket')
                                            ->placeholder('0'),
                                    ]),

                                Placeholder::make('perhitungan_info')
                                    ->label('Informasi Perhitungan')
                                    ->content(fn ($get) => $get('metode_pembayaran') == 'otomatis' 
                                        ? 'Harga telah dihitung otomatis berdasarkan paket layanan dan brand yang dipilih, sudah termasuk pajak.'
                                        : 'Masukkan total harga manual sesuai perhitungan prorate.')
                                    ->columnSpan(2),
                            ]),
                    ]),

                Section::make('Informasi Detail Pelanggan')
                    ->description('Data lengkap pelanggan')
                    ->icon('heroicon-o-information-circle')
                    ->collapsed()
                    ->collapsible()
                    ->schema([
                        Placeholder::make('detail_pelanggan')
                            ->content(function (callable $get) {
                                $pelangganId = $get('pelanggan_id');
                                if (!$pelangganId) return 'Pilih pelanggan terlebih dahulu';

                                $pelanggan = Pelanggan::find($pelangganId);
                                if (!$pelanggan) return 'Pelanggan tidak ditemukan';

                                $info = "<div class='space-y-3'>";
                                $info .= "<div class='p-3 bg-gray-50 rounded-lg border border-gray-200'>";
                                $info .= "<div class='font-medium text-gray-700 mb-1'>Data Pribadi:</div>";
                                $info .= "<div class='grid grid-cols-2 gap-2 text-sm'>";
                                $info .= "<div><strong>Nama:</strong> {$pelanggan->nama}</div>";
                                $info .= "<div><strong>No. KTP:</strong> {$pelanggan->no_ktp}</div>";
                                $info .= "<div><strong>Email:</strong> {$pelanggan->email}</div>";
                                $info .= "<div><strong>No. Telp:</strong> {$pelanggan->no_telp}</div>";
                                $info .= "</div></div>";

                                $info .= "<div class='p-3 bg-gray-50 rounded-lg border border-gray-200'>";
                                $info .= "<div class='font-medium text-gray-700 mb-1'>Alamat:</div>";
                                $info .= "<div class='text-sm'>";
                                $info .= "<div><strong>Alamat:</strong> " . ($pelanggan->alamat === 'Lainnya' ? $pelanggan->alamat_custom : $pelanggan->alamat) . "</div>";
                                $info .= "<div><strong>Blok/Unit:</strong> {$pelanggan->blok}/{$pelanggan->unit}</div>";
                                if ($pelanggan->alamat_2) {
                                    $info .= "<div><strong>Alamat Tambahan:</strong> {$pelanggan->alamat_2}</div>";
                                }
                                $info .= "</div></div>";

                                if ($pelanggan->id_brand || $pelanggan->layanan) {
                                    $info .= "<div class='p-3 bg-blue-50 rounded-lg border border-blue-200'>";
                                    $info .= "<div class='font-medium text-blue-700 mb-1'>Data Default Layanan:</div>";
                                    $info .= "<div class='text-sm space-y-1'>";
                                    $brandName = HargaLayanan::where('id_brand', $pelanggan->id_brand)->first()?->brand ?? $pelanggan->id_brand;
                                    $info .= "<div><strong>Brand Default:</strong> " . $brandName . "</div>";
                                    $info .= "<div><strong>Paket Default:</strong> " . ($pelanggan->layanan ?? 'Belum diatur') . "</div>";
                                    if ($pelanggan->tgl_instalasi) {
                                        $info .= "<div><strong>Tanggal Instalasi:</strong> " . Carbon::parse($pelanggan->tgl_instalasi)->format('d M Y') . "</div>";
                                    }
                                    $info .= "</div></div>";
                                }

                                $info .= "</div>";

                                return new HtmlString($info);
                            }),
                    ]),
            ]);
    }

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

                    $harga = match ($layanan) {
                        '10 Mbps' => $hargaLayanan->harga_10mbps,
                        '20 Mbps' => $hargaLayanan->harga_20mbps,
                        '30 Mbps' => $hargaLayanan->harga_30mbps,
                        '50 Mbps' => $hargaLayanan->harga_50mbps,
                        default => 0,
                    };

                    $pajak = floor(($hargaLayanan->pajak / 100) * $harga);
                    $total = $harga + $pajak;
                    $totalBulat = ceil($total / 1000) * 1000;

                    if ($brandId === 'ajn-01') {
                        if ($layanan === '10 Mbps') $totalBulat = 150000;
                        else if ($layanan === '20 Mbps') $totalBulat = 220890;
                        else if ($layanan === '30 Mbps') $totalBulat = 248640;
                        else if ($layanan === '50 Mbps') $totalBulat = 281940;
                    }

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
                        Notification::make()
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
     */
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('user_status', 'Suspend')->count();
    }

    /**
     * Get the color of the badge to display in the navigation.
     */
    public static function getNavigationBadgeColor(): ?string
    {
        $suspendedCount = static::getModel()::where('user_status', 'Suspend')->count();

        if ($suspendedCount === 0) {
            return 'success';
        } elseif ($suspendedCount < 5) {
            return 'warning';
        } elseif ($suspendedCount < 10) {
            return 'danger';
        } else {
            return 'primary';
        }
    }

    /**
     * Get the tooltip for the badge in the navigation.
     */
    public static function getNavigationBadgeTooltip(): ?string
    {
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