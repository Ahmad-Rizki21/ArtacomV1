<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DataTeknisResource\Pages;
use App\Models\DataTeknis;
use App\Models\Pelanggan;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TextFilter;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;

class DataTeknisResource extends Resource
{
    protected static ?string $model = DataTeknis::class;

    protected static ?string $navigationLabel = 'Data Teknis';
    protected static ?string $navigationIcon = 'heroicon-o-server'; // Ikon yang lebih sesuai
    protected static ?string $navigationGroup = 'FTTH'; // Mengelompokkan dalam grup "FTTH"

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Select::make('pelanggan_id')
                    ->label('Pelanggan')
                    ->options(Pelanggan::pluck('nama', 'id')) // Dropdown pelanggan
                    ->searchable()
                    ->required()  // Wajib diisi
                    ->live()
                    ->afterStateUpdated(fn ($state, callable $set) => 
                        $set('id_pelanggan', Pelanggan::where('id', $state)->value('id'))
                    ),

                    TextInput::make('id_vlan')
                    ->label('ID VLAN')
                    ->required(),

                // ID Pelanggan
                TextInput::make('id_pelanggan')
                    ->label('ID Pelanggan')
                    ->required(),  // Wajib diisi

                // IP Pelanggan
                TextInput::make('ip_pelanggan')
                    ->label('IP Pelanggan')
                    ->required(),  // Wajib diisi

                // Password PPPoE
                TextInput::make('password_pppoe')
                    ->label('Password PPPoE')
                    ->required(),  // Wajib diisi

                    TextInput::make('profile_pppoe')
                    ->label('Profile PPPoE')
                    ->required(),
                
                // Dropdown untuk memilih lokasi OLT
                Select::make('olt')
                ->label('Pilih Lokasi OLT')
                ->options([
                    'Nagrak' => 'Rusun Nagrak',
                    'Pinus Elok' => 'Rusun Pinus Elok',
                    'Pulogebang Tower' => 'Rusun Pulogebang Tower',
                    'Pulogebang Blok' => 'Rusun Pulogebang Blok',
                    'KM2' => 'Rusun KM2',
                    'Tipar Cakung' => 'Rusun Tipar Cakung',
                    'Tambun' => 'Rusun Tambun',
                    'Pinus (Luar)' => 'Rusun Pinus (Luar)',
                    'Pinus (KM2)' => 'Rusun Pinus (KM2)',
                    'Albo' => 'Rusun Albo',
                    'Lainnya' => 'Lainnya',
                ])
                ->placeholder('Pilih OLT Rusun')
                ->helperText('Pilih lokasi rusun atau pilih "Lainnya" jika tidak ada')
                ->reactive()
                ->afterStateUpdated(function ($state, callable $set) {
                    if ($state === 'Lainnya') {
                        $set('olt_custom', ''); // Reset the custom input if "Lainnya" is selected
                    } else {
                        $set('olt_custom', null); // Reset custom input if a dropdown option is selected
                    }
                }),

        TextInput::make('olt_custom')
            ->label('Masukan Lokasi OLT')
            ->placeholder('Masukkan OLT rusun lainnya')
            ->nullable()
            ->helperText('Masukkan alamat lain jika pilihan rusun tidak tersedia')
            ->visible(fn ($get) => $get('olt') === 'Lainnya'),


                
                TextInput::make('pon')
                    ->label('PON')
                    ->default(0) // Set default value as 0
                    ->helperText('Kolom ini wajib di isi ketika Teknisi sudah menyerahkan data nya'),
                
                TextInput::make('otb')
                    ->label('OTB')
                    ->default(0) // Set default value as 0
                    ->helperText('Kolom ini wajib di isi ketika Teknisi sudah menyerahkan data nya'),
                
                TextInput::make('odc')
                    ->label('ODC')
                    ->default(0) // Set default value as 0
                    ->helperText('Kolom ini wajib di isi ketika Teknisi sudah menyerahkan data nya'),
                
                TextInput::make('odp')
                    ->label('ODP')
                    ->default(0) // Set default value as 0
                    ->helperText('Kolom ini wajib di isi ketika Teknisi sudah menyerahkan data nya'),
                
                TextInput::make('onu_power')
                    ->label('ONU Power')
                    ->default(0) // Set default value as 0
                    ->helperText('Kolom ini wajib di isi ketika Teknisi sudah menyerahkan data nya'),
                
                
            ]);
    }


    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('pelanggan.nama')
                ->label('Pelanggan') // Mengubah label menjadi "Pelanggan"
                ->sortable() // Menambahkan kemampuan untuk mengurutkan berdasarkan nama
                ->searchable(), // Menambahkan kemampuan untuk mencari berdasarkan nama

                TextColumn::make('id_vlan')->label('ID VLAN')->sortable()->searchable(),
                TextColumn::make('id_pelanggan')->label('ID Pelanggan')->sortable()->searchable(),
                TextColumn::make('password_pppoe')->label('Password PPPoE')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ip_pelanggan')->label('IP Pelanggan')->sortable(),
                TextColumn::make('profile_pppoe')->label('Profile PPPoE'),
                
                // TextColumn::make('olt')
                // ->label('OLT')
                // ->getStateUsing(function ($record) {
                //     if ($record->olt === 'Lainnya' && $record->olt_custom) {
                //         return $record->olt_custom; // Display custom location if 'Lainnya' is selected
                //     }
                //     return $record->olt; // Otherwise, show the regular value
                // })
                // ->sortable()
                // ->searchable()
                // ->limit(50)
                // ->tooltip(fn ($record) => $record->olt),
                TextColumn::make('olt')->label('OLT')
                ->getStateUsing(function ($record) {
                    // Jika 'olt' berisi 'Lainnya', tampilkan nilai dari 'olt_custom'
                    if ($record->olt === 'Lainnya' && $record->olt_custom) {
                        return $record->olt_custom; // Menampilkan input manual jika ada
                    }

                    return $record->olt; // Menampilkan nilai dropdown jika tidak 'Lainnya'
                }),



                TextColumn::make('pon')->label('PON'),
                TextColumn::make('otb')->label('OTB'),
                TextColumn::make('odc')->label('ODC'),
                TextColumn::make('odp')->label('ODP'),
                TextColumn::make('onu_power')->label('ONU Power'),
            ])
            ->filters([
                SelectFilter::make('olt')
                    ->label('Filter OLT')
                    ->options([
                        'Rusun Nagrak' => 'Rusun Nagrak',
                        'Rusun Pinus Elok' => 'Rusun Pinus Elok',
                        'Rusun Pulogebang Tower' => 'Rusun Pulogebang Tower',
                        'Rusun Pulogebang Blok' => 'Rusun Pulogebang Blok',
                        'Rusun KM2' => 'Rusun KM2',
                        'Rusun Tipar Cakung' => 'Rusun Tipar Cakung',
                        'Rusun Tambun' => 'Rusun Tambun',
                        'Rusun Pinus (Luar)' => 'Rusun Pinus (Luar)',
                        'Rusun Pinus (KM2)' => 'Rusun Pinus (KM2)',
                        'Rusun Albo' => 'Rusun Albo',
                        'Lainnya' => 'Lainnya', // Opsi untuk input manual
                    ])
            ])
            ->actions([
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
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }


    protected static function boot()
{
    parent::boot();
    static::creating(function ($dataTeknis) {
        if (!$dataTeknis->id_pelanggan) {
            $dataTeknis->id_pelanggan = 'UNKNOWN'; // 🔥 Default jika kosong
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
