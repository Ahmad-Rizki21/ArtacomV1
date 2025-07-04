<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DataTeknisResource\Pages;
use App\Models\DataTeknis;
use App\Models\Pelanggan;
use App\Models\Langganan;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Actions\ActionGroup;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Fieldset;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use Filament\Notifications\Notification;

class DataTeknisResource extends Resource
{
    protected static ?string $model = DataTeknis::class;

    protected static ?string $navigationLabel = 'Data Teknis';
    protected static ?string $navigationIcon = 'heroicon-o-server';
    protected static ?string $navigationGroup = 'FTTH';
    protected static ?string $recordTitleAttribute = 'id_pelanggan';
    protected static ?int $navigationSort = -2;
    

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Section::make('Informasi Pelanggan')
                    ->description('Data identitas pelanggan yang terhubung dengan informasi teknis ini')
                    ->schema([
                        Grid::make()
                            ->schema([
                                Select::make('pelanggan_id')
                                ->label('Pelanggan')
                                ->options(Pelanggan::pluck('nama', 'id'))
                                ->searchable()
                                ->required()
                                ->preload()
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, $get) {
                                    if ($state) {
                                        $pelanggan = Pelanggan::find($state);
                                        if ($pelanggan) {
                                            // Cari langganan aktif dulu
                                            $langganan = Langganan::where('pelanggan_id', $pelanggan->id)
                                                                ->where('user_status', 'Aktif')
                                                                ->first();

                                            if ($langganan) {
                                                // Ada langganan aktif, tampilkan data langganan
                                                $set('info_layanan', $langganan->layanan ?? 'Tidak ada data');
                                                $set('info_brand', $langganan->id_brand ?? 'Tidak ada data');
                                                $set('info_status', $langganan->user_status ?? 'Tidak ada data');
                                            } else {
                                                // Tidak ada langganan aktif, tampilkan data default dari pelanggan
                                                $set('info_layanan', $pelanggan->layanan ?? 'Tidak ada data langganan');
                                                $set('info_brand', $pelanggan->id_brand ?? 'Tidak ada data langganan');
                                                $set('info_status', 'Belum Berlangganan');
                                            }
                                        }
                                    }
                                })
                                ->columnSpan(2),

                                TextInput::make('id_pelanggan')
                                    ->label('ID Pelanggan')
                                    ->required()
                                    ->maxLength(50)
                                    ->helperText('Masukkan ID Pelanggan secara manual')
                                    ->columnSpan(1),
                            ]),
                            
                        Card::make()
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        TextInput::make('info_layanan')
                                        ->label('Paket Layanan')
                                        ->helperText('Paket kecepatan yang digunakan')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->afterStateHydrated(function ($state, $set, $record) {
                                            if ($record) {
                                                $set('info_layanan', $record->langganan->layanan ?? $record->pelanggan->layanan ?? 'Tidak ada data');
                                            }
                                        }),

                                    TextInput::make('info_brand')
                                        ->label('Brand Layanan')
                                        ->helperText('Provider internet yang digunakan')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->afterStateHydrated(function ($state, $set, $record) {
                                            if ($record) {
                                                $set('info_brand', $record->langganan->id_brand ?? $record->pelanggan->id_brand ?? 'Tidak ada data');
                                            }
                                        }),

                                    TextInput::make('info_status')
                                        ->label('Status Layanan')
                                        ->helperText('Status layanan internet')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->afterStateHydrated(function ($state, $set, $record) {
                                            if ($record) {
                                                $set('info_status', $record->langganan->user_status ?? 'Belum Berlangganan');
                                            }
                                        }),
                                    ]),
                                    
                                Placeholder::make('info_layanan_notes')
                                    ->content(new HtmlString('
                                        <div class="p-2 bg-blue-50 rounded-lg border border-blue-200">
                                            <div class="text-blue-700 font-medium text-sm">Informasi Layanan:</div>
                                            <p class="text-sm text-blue-600 mt-1">
                                                Informasi di atas diambil dari data langganan pelanggan dan hanya bersifat informatif.
                                                Perubahan pada data teknis tidak akan mempengaruhi data langganan.
                                            </p>
                                        </div>
                                    '))
                                    ->columnSpan('full'),
                            ])
                            ->columnSpan('full')
                            ->hidden(fn ($get) => !$get('pelanggan_id')),
                    ]),

                Tabs::make('Konfigurasi Teknis')
                    ->tabs([
                        Tabs\Tab::make('Internet Settings')
                            ->icon('heroicon-o-globe-alt')
                            ->schema([
                                Card::make()
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('id_vlan')
                                            ->label('ID VLAN')
                                            ->required()
                                            ->maxLength(50)
                                            ->prefix('VLAN')
                                            ->helperText('ID VLAN untuk pelanggan ini'),

                                        TextInput::make('ip_pelanggan')
                                            ->label('IP Pelanggan')
                                            ->required()
                                            ->placeholder('192.168.1.1')
                                            ->helperText('Format: xxx.xxx.xxx.xxx')
                                            ->regex('/^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$/')
                                            ->validationAttribute('alamat IP'),

                                        TextInput::make('password_pppoe')
                                            ->label('Password PPPoE')
                                            ->required()
                                            ->password()
                                            ->revealable()
                                            ->maxLength(50)
                                            ->helperText('Password untuk authentikasi PPPoE'),

                                        Select::make('profile_pppoe')
                                            ->label('Profile PPPoE')
                                            ->options(function () {
                                                $profiles = [];
                                                $speeds = [10, 20, 30, 50];
                                                $suffixes = range('a', 'z');
                                        
                                                foreach ($speeds as $speed) {
                                                    foreach ($suffixes as $suffix) {
                                                        $profiles[$speed . 'Mbps-' . $suffix] = $speed . ' Mbps-' . $suffix;
                                                    }
                                                }
                                        
                                                return $profiles;
                                            })
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                if ($state) {
                                                    $matches = [];
                                                    if (preg_match('/(\d+)Mbps/', $state, $matches)) {
                                                        $kecepatan = $matches[1] . ' Mbps';
                                                        $set('info_extracted_speed', $kecepatan);
                                                        
                                                        Log::info('Kecepatan berhasil diekstrak', [
                                                            'profile' => $state,
                                                            'kecepatan' => $kecepatan
                                                        ]);
                                                    } else {
                                                        $set('info_extracted_speed', 'Tidak dapat menentukan kecepatan');
                                                        
                                                        Log::warning('Gagal mengekstrak kecepatan', [
                                                            'profile' => $state
                                                        ]);
                                                    }
                                                }
                                            })
                                            ->helperText('Paket kecepatan internet yang digunakan'),
                                            
                                        TextInput::make('info_extracted_speed')
                                        ->label('Kecepatan Internet')
                                        ->helperText('Kecepatan berdasarkan profile PPPoE')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->visible(fn ($get) => !empty($get('profile_pppoe')))
                                        ->afterStateHydrated(function ($state, $set, $record) {
                                            if ($record && !empty($record->profile_pppoe)) {
                                                $matches = [];
                                                if (preg_match('/(\d+)Mbps/', $record->profile_pppoe, $matches)) {
                                                    $set('info_extracted_speed', $matches[1] . ' Mbps');
                                                } else {
                                                    $set('info_extracted_speed', 'Tidak dapat menentukan kecepatan');
                                                }
                                            }
                                        }),
                                    ]),
                            ]),

                        Tabs\Tab::make('Lokasi & Peralatan')
                            ->icon('heroicon-o-map-pin')
                            ->schema([
                                Card::make()
                                    ->columns(1)
                                    ->schema([
                                        Select::make('olt')
                                            ->label('Lokasi OLT')
                                            ->options([
                                                'Nagrak' => 'Rusun Nagrak',
                                                'Pinus Elok' => 'Pinus Elok',
                                                'Pulogebang Tower' => 'Rusun Pulogebang Tower',
                                                'Tipar Cakung' => 'Rusun Tipar Cakung',
                                                'Tambun' => 'Tambun',
                                                'Parama' => 'Parama',
                                                'Waringin' => 'Waringin',
                                                'Lainnya' => 'Lainnya',
                                            ])
                                            ->placeholder('Pilih OLT Rusun')
                                            ->helperText('Pilih lokasi rusun atau pilih "Lainnya" jika tidak ada')
                                            ->required()
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                if ($state === 'Lainnya') {
                                                    $set('olt_custom', '');
                                                } else {
                                                    $set('olt_custom', null);
                                                }
                                            }),

                                        TextInput::make('olt_custom')
                                            ->label('Lokasi OLT Kustom')
                                            ->placeholder('Masukkan OLT rusun lainnya')
                                            ->nullable()
                                            ->maxLength(100)
                                            ->visible(fn ($get) => $get('olt') === 'Lainnya'),

                                        Fieldset::make('Detail Jaringan')
                                            ->schema([
                                                Grid::make()
                                                    ->schema([
                                                        TextInput::make('pon')
                                                            ->label('PON')
                                                            ->default(0)
                                                            ->numeric()
                                                            ->minValue(0),
                                                        
                                                        TextInput::make('otb')
                                                            ->label('OTB')
                                                            ->default(0)
                                                            ->numeric()
                                                            ->minValue(0),
                                                        
                                                        TextInput::make('odc')
                                                            ->label('ODC')
                                                            ->default(0)
                                                            ->numeric()
                                                            ->minValue(0),
                                                        
                                                        TextInput::make('odp')
                                                            ->label('ODP')
                                                            ->default(0)
                                                            ->numeric()
                                                            ->minValue(0),
                                                        
                                                        TextInput::make('onu_power')
                                                            ->label('ONU Power (dBm)')
                                                            ->default(0)
                                                            ->numeric()
                                                            ->suffix('dBm')
                                                            ->helperText('Kekuatan sinyal ONU dalam dBm'),
                                                    ]),
                                            ]),

                                        \Filament\Forms\Components\FileUpload::make('speedtest_proof')
                                            ->label('Bukti Speedtest')
                                            ->disk('public')
                                            ->directory('speedtest_proofs')
                                            ->acceptedFileTypes(['image/*', 'application/pdf'])
                                            ->maxSize(2048)
                                            ->helperText('Upload bukti speedtest (opsional, maksimal 2MB, format: gambar atau PDF)')
                                            ->required(false)
                                            ->imagePreviewHeight('200')
                                            ->columnSpan('full'),
                                    ]),
                            ]),
                    ])
                    ->activeTab(1)
                    ->columnSpan('full'),

                Placeholder::make('catatan')
                    ->content(new HtmlString('
                        <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                            <h3 class="text-blue-700 font-medium text-sm">Panduan Pengisian Data:</h3>
                            <ul class="mt-2 text-sm text-blue-600 space-y-1 list-disc list-inside">
                                <li>ID Pelanggan diisi secara manual</li>
                                <li>Detail jaringan akan diisi oleh teknisi setelah kunjungan lokasi</li>
                                <li>Profil PPPoE menentukan paket internet yang akan digunakan pelanggan</li>
                                <li>ONU Power harusnya memiliki nilai negatif (misalnya -20dBm)</li>
                                <li>Bukti speedtest dapat diupload setelah pengujian jaringan</li>
                            </ul>
                        </div>
                    '))
                    ->columnSpan('full'),
            ]);
    }

    /**
     * Customizes the table columns and actions.
     *
     * @param Tables\Table $table
     * @return Tables\Table
     */
    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                ->label('No.')
                ->rowIndex()
                ->sortable(false)
                ->searchable(false)
                ->toggleable(),

                TextColumn::make('pelanggan.nama')
                    ->label('Pelanggan')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->wrap(),

                TextColumn::make('id_vlan')
                    ->label('ID VLAN')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('id_pelanggan')
                    ->label('ID Pelanggan')
                    ->sortable()
                    ->searchable()
                    ->copyable()
                    ->toggleable(),

                TextColumn::make('ip_pelanggan')
                    ->label('IP Pelanggan')
                    ->sortable()
                    ->searchable()
                    ->copyable(),
                
                TextColumn::make('profile_pppoe')
                    ->label('Profile PPPoE')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                
                TextColumn::make('olt')
                    ->label('OLT')
                    ->getStateUsing(function ($record) {
                        if ($record->olt === 'Lainnya' && $record->olt_custom) {
                            return $record->olt_custom;
                        }
                        return $record->olt;
                    })
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('onu_power')
                    ->label('ONU Power')
                    ->sortable()
                    ->suffix(' dBm')
                    ->toggleable(),
                
                TextColumn::make('password_pppoe')
                    ->label('Password PPPoE')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('pon')
                    ->label('PON')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('otb')
                    ->label('OTB')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('odc')
                    ->label('ODC')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('odp')
                    ->label('ODP')
                    ->toggleable(isToggledHiddenByDefault: true),

                ImageColumn::make('speedtest_proof')
                    ->label('Bukti Speedtest')
                    ->disk('public')
                    ->toggleable()
                    ->height(80)
                    ->width(null)
                    ->placeholder('Tidak ada gambar')
                    ->openUrlInNewTab(),

            ])
            ->defaultSort('id', 'asc')
            ->filters([
                SelectFilter::make('olt')
                    ->label('Filter OLT')
                    ->options([
                        'Nagrak' => 'Rusun Nagrak',
                        'Pinus Elok' => 'Pinus Elok',
                        'Pulogebang Tower' => 'Rusun Pulogebang Tower',
                        'Tipar Cakung' => 'Rusun Tipar Cakung',
                        'Tambun' => 'Tambun',
                        'Parama' => 'Perumahan Parama',
                        'Waringin' => 'Perumahan Waringin',
                        'Lainnya' => 'Lainnya',
                    ])
                    ->indicator('OLT'),
                
                SelectFilter::make('profile_pppoe')
                    ->label('Filter Paket')
                    ->options(function () {
                        $profiles = [];
                        $speeds = [10, 20, 30, 50];
                        
                        foreach ($speeds as $speed) {
                            $profiles[$speed . 'Mbps'] = $speed . ' Mbps';
                        }
                        
                        return $profiles;
                    })
                    ->query(function ($query, array $data) {
                        if (!empty($data['value'])) {
                            return $query->where('profile_pppoe', 'like', $data['value'] . '%');
                        }
                        
                        return $query;
                    })
                    ->indicator('Paket'),
                    
                SelectFilter::make('user_status')
                    ->label('Status Pelanggan')
                    ->options([
                        'Aktif' => 'Aktif',
                        'Suspend' => 'Suspend',
                        'Tidak Ada Invoice' => 'Tidak Ada Invoice'
                    ])
                    ->query(function ($query, array $data) {
                        if (!empty($data['value'])) {
                            return $query->whereHas('pelanggan.langganan', function ($q) use ($data) {
                                $q->where('user_status', $data['value']);
                            });
                        }
                        return $query;
                    })
                    ->indicator('Status'),
            ])
            ->actions([
                Tables\Actions\Action::make('aksesModem')
                    ->label('Akses Modem')
                    ->icon('heroicon-o-computer-desktop')
                    ->color(function (DataTeknis $record) {
                        if ($record->pelanggan) {
                            $userStatus = DB::table('langganan')
                                ->where('pelanggan_id', $record->pelanggan->id)
                                ->value('user_status');
                            
                            if ($userStatus === 'Suspend') {
                                return 'danger';
                            }
                        }
                        
                        return 'success';
                    })
                    ->url(function (DataTeknis $record) {
                        if ($record->pelanggan) {
                            $userStatus = DB::table('langganan')
                                ->where('pelanggan_id', $record->pelanggan->id)
                                ->value('user_status');
                            
                            if ($userStatus === 'Suspend') {
                                return null;
                            }
                        }
                        
                        return "http://{$record->ip_pelanggan}";
                    })
                    ->openUrlInNewTab()
                    ->requiresConfirmation()
                    ->modalHeading(function (DataTeknis $record) {
                        if ($record->pelanggan) {
                            $userStatus = DB::table('langganan')
                                ->where('pelanggan_id', $record->pelanggan->id)
                                ->value('user_status');
                            
                            if ($userStatus === 'Suspend') {
                                return 'Akses Ditolak';
                            }
                        }
                        
                        return 'Konfirmasi Akses Modem';
                    })
                    ->modalDescription(function (DataTeknis $record) {
                        if ($record->pelanggan) {
                            $userStatus = DB::table('langganan')
                                ->where('pelanggan_id', $record->pelanggan->id)
                                ->value('user_status');
                            
                            if ($userStatus === 'Suspend') {
                                return 'Peringatan: User ini sedang dalam masa suspended maka anda tidak bisa masuk kedalam konfigurasi modem';
                            }
                        }
                        
                        return "Anda akan mengakses modem dengan IP: {$record->ip_pelanggan}";
                    })
                    ->modalSubmitActionLabel(function (DataTeknis $record) {
                        if ($record->pelanggan) {
                            $userStatus = DB::table('langganan')
                                ->where('pelanggan_id', $record->pelanggan->id)
                                ->value('user_status');
                            
                            if ($userStatus === 'Suspend') {
                                return 'Kembali';
                            }
                        }
                        
                        return 'Lanjutkan';
                    })
                    ->modalCancelActionLabel('Batal'),

                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Konfirmasi Hapus Data Teknis')
                        ->modalDescription('Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.')
                        ->modalSubmitActionLabel('Ya, Hapus')
                        ->modalCancelActionLabel('Batal')
                        ->successNotificationTitle('🗑️ Data Teknis Berhasil Dihapus!')
                        ->after(function () {
                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('🗑️ Data Teknis Telah Dihapus!')
                                ->body('Data Teknis ini telah dihapus secara permanen.')
                                ->send();
                        }),
                ])
                ->tooltip('Aksi')
                ->icon('heroicon-m-ellipsis-vertical'),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Data Teknis Terpilih')
                    ->modalDescription('Apakah Anda yakin ingin menghapus data yang terpilih? Tindakan ini tidak dapat dibatalkan.'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDataTeknis::route('/'),
            'create' => Pages\CreateDataTeknis::route('/create'),
            'edit' => Pages\EditDataTeknis::route('/{record}/edit'),
        ];
    }
}
