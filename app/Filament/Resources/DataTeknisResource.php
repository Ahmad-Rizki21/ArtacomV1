<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DataTeknisResource\Pages;
use App\Models\DataTeknis;
use App\Models\Pelanggan;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TextFilter;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Actions\ActionGroup;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Tabs;
use Illuminate\Support\HtmlString;

class DataTeknisResource extends Resource
{
    protected static ?string $model = DataTeknis::class;

    protected static ?string $navigationLabel = 'Data Teknis';
    protected static ?string $navigationIcon = 'heroicon-o-server';
    protected static ?string $navigationGroup = 'FTTH';
    protected static ?string $recordTitleAttribute = 'id_pelanggan';
    protected static ?int $navigationSort = 2;

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
                                    ->columnSpan(2),

                                TextInput::make('id_pelanggan')
                                    ->label('ID Pelanggan')
                                    ->required()
                                    ->maxLength(50)
                                    ->helperText('Masukkan ID Pelanggan secara manual')
                                    ->columnSpan(1),
                            ]),
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
                                            ->helperText('Paket kecepatan internet yang digunakan'),
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
                                                'Pinus Elok' => 'Rusun Pinus Elok',
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
                            </ul>
                        </div>
                    '))
                    ->columnSpan('full'),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('No.')
                    ->sortable()
                    ->searchable()
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
                    ->copyable()
                    ->toggleable(),

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
            ])
            ->defaultSort('id', 'asc')
            ->filters([
                SelectFilter::make('olt')
                    ->label('Filter OLT')
                    ->options([
                        'Nagrak' => 'Rusun Nagrak',
                        'Pinus Elok' => 'Rusun Pinus Elok',
                        'Pulogebang Tower' => 'Rusun Pulogebang Tower',
                        'Tipar Cakung' => 'Rusun Tipar Cakung',
                        'Tambun' => 'Rusun Tambun',
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
            ])
            ->actions([
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

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($dataTeknis) {
            if (!$dataTeknis->id_pelanggan) {
                $dataTeknis->id_pelanggan = 'UNKNOWN';
            }
        });
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