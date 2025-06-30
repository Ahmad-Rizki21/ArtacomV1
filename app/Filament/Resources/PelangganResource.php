<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PelangganResource\Pages;
use App\Models\Pelanggan;
use App\Models\HargaLayanan;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Log;

class PelangganResource extends Resource
{
    protected static ?string $model = Pelanggan::class;
    
    protected static ?string $navigationLabel = 'Data Pelanggan';
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'FTTH';
    protected static ?int $navigationSort = -4;
    protected static ?string $recordTitleAttribute = 'nama';
    

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Pribadi')
                    ->description('Data identitas pelanggan')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        Grid::make()
                            ->schema([
                                TextInput::make('no_ktp')
                                    ->label('No. KTP')
                                    ->required()
                                    ->maxLength(16)
                                    ->minLength(16)
                                    ->unique(ignoreRecord: true)
                                    ->numeric()
                                    ->placeholder('Masukkan No. KTP (16 digit)')
                                    ->mask('9999999999999999')
                                    ->helperText('Masukkan 16 digit nomor KTP tanpa spasi')
                                    ->columnSpan(1),

                                TextInput::make('nama')
                                    ->label('Nama Lengkap')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Masukkan Nama Lengkap')
                                    ->helperText('Nama sesuai KTP')
                                    ->columnSpan(1),

                                TextInput::make('no_telp')
                                    ->label('No. Telepon')
                                    ->required()
                                    ->maxLength(15)
                                    ->tel()
                                    ->prefix('+62/08')
                                    ->placeholder('08xxxxxxxxxx')
                                    ->helperText('Contoh: 0812345678'),

                                TextInput::make('email')
                                    ->label('Email')
                                    ->required()
                                    ->email()
                                    ->maxLength(255)
                                    ->placeholder('Masukkan Email Aktif')
                                    ->helperText('Email yang aktif untuk komunikasi')
                                    ->columnSpan(1),

                                DatePicker::make('tgl_instalasi')
                                    ->label('Tanggal Instalasi')
                                    ->placeholder('Pilih tanggal instalasi')
                                    ->helperText('Tanggal pemasangan internet')
                                    ->displayFormat('d M Y')
                                    ->closeOnDateSelection()
                                    ->columnSpan(1),
                            ]),
                    ]),

                Section::make('Informasi Alamat')
                    ->description('Alamat lengkap pelanggan')
                    ->icon('heroicon-o-map-pin')
                    ->collapsible()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Card::make()
                                    ->schema([
                                        Select::make('alamat')
                                            ->label('Alamat (Rusun)')
                                            ->options([
                                                'Rusun Nagrak' => 'Rusun Nagrak',
                                                'Rusun Pinus Elok' => 'Rusun Pinus Elok',
                                                'Luar Pinus Elok' => 'Luar Pinus Elok',
                                                'Rusun Pulogebang' => 'Rusun Pulogebang Tower',
                                                'Rusun KM2' => 'Rusun KM2',
                                                'Rusun Tipar Cakung' => 'Rusun Tipar Cakung',
                                                'Rusun Albo' => 'Rusun Albo',
                                                'Perumahan Tambun' => 'Perumahan Tambun',
                                                'Perumahan Waringin' => 'Perumahan Waringin Kurung',
                                                'Perumahan Parama' => 'Perumahan Parama Serang',
                                                'Lainnya' => 'Lainnya'
                                            ])
                                            ->searchable()
                                            ->preload()
                                            ->placeholder('Pilih Rusun')
                                            ->helperText('Pilih lokasi rusun atau pilih "Lainnya" jika tidak ada')
                                            ->required()
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                if ($state === 'Lainnya') {
                                                    $set('alamat_custom', '');
                                                } else {
                                                    $set('alamat_custom', null);
                                                }
                                            }),

                                        TextInput::make('alamat_custom')
                                            ->label('Alamat Lainnya')
                                            ->placeholder('Masukkan alamat rusun lainnya')
                                            ->nullable()
                                            ->maxLength(255)
                                            ->helperText('Masukkan alamat lain jika pilihan rusun tidak tersedia')
                                            ->visible(fn ($get) => $get('alamat') === 'Lainnya'),

                                        Fieldset::make('Detail Unit')
                                            ->schema([
                                                TextInput::make('blok')
                                                    ->label('Blok')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->placeholder('Contoh: A, B, C')
                                                    ->helperText('Blok tempat tinggal'),

                                                TextInput::make('unit')
                                                    ->label('Unit')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->placeholder('Contoh: 101, 23A')
                                                    ->helperText('Nomor unit tempat tinggal'),
                                            ]),
                                    ])
                                    ->columnSpan(1),

                                Card::make()
                                    ->schema([
                                        TextArea::make('alamat_2')
                                            ->label('Alamat Lain (Opsional)')
                                            ->nullable()
                                            ->placeholder('Masukkan alamat tambahan jika ada')
                                            ->helperText('Masukkan detail alamat tambahan seperti nama gedung, lantai, dll')
                                            ->rows(4),

                                        Placeholder::make('alamat_tips')
                                            ->content(new HtmlString('
                                                <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                                                    <h3 class="text-yellow-700 font-medium text-sm">Tips Pengisian Alamat:</h3>
                                                    <ul class="mt-2 text-sm text-yellow-600 space-y-1 list-disc list-inside">
                                                        <li>Pastikan alamat diisi dengan lengkap</li>
                                                        <li>Untuk rusun, isi detail blok dan unit dengan jelas</li>
                                                        <li>Alamat tambahan bisa diisi dengan patokan lokasi</li>
                                                    </ul>
                                                </div>
                                            ')),
                                    ])
                                    ->columnSpan(1),
                            ]),
                    ]),

                // Bagian baru untuk informasi layanan
                Section::make('Informasi Layanan')
                    ->description('Data layanan internet pelanggan')
                    ->icon('heroicon-o-wifi')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('id_brand')
                                    ->label('Brand Layanan')
                                    ->options(HargaLayanan::pluck('brand', 'id_brand'))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        // Simpan brand default untuk pelanggan ini
                                        $set('brand_default', $state);
                                        
                                        // Log untuk debugging
                                        Log::info('Brand Terpilih:', ['id_brand' => $state]);
                                    }),

                                Select::make('layanan')
                                    ->label('Paket Layanan')
                                    ->options([
                                        '10 Mbps' => '10 Mbps',
                                        '20 Mbps' => '20 Mbps',
                                        '30 Mbps' => '30 Mbps', 
                                        '50 Mbps' => '50 Mbps',
                                    ])
                                    ->required()
                                    ->reactive(),
                            ]),
                        
                        Placeholder::make('layanan_tips')
                            ->content(new HtmlString('
                                <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                                    <h3 class="text-blue-700 font-medium text-sm">Informasi Layanan:</h3>
                                    <ul class="mt-2 text-sm text-blue-600 space-y-1 list-disc list-inside">
                                        <li>Brand dan paket yang dipilih akan menjadi default saat membuat langganan baru</li>
                                        <li>Pemilihan brand akan menentukan harga paket internet</li>
                                        <li>Pelanggan dapat memiliki beberapa langganan dengan brand berbeda</li>
                                    </ul>
                                </div>
                            '))
                            ->columnSpan('full'),
                    ]),

                Placeholder::make('note')
                    ->content(new HtmlString('
                        <div class="p-4 rounded-lg border border-indigo-200 bg-indigo-50">
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="text-indigo-700 font-medium">Catatan Penting</span>
                            </div>
                            <p class="mt-2 text-sm text-indigo-600">Data yang dimasukkan akan digunakan untuk keperluan administrasi dan instalasi internet. Pastikan data sudah benar dan lengkap sebelum menyimpan.</p>
                        </div>
                    '))
                    ->columnSpan('full'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
               TextColumn::make('id')
                ->label('No.')
                ->rowIndex()
                ->sortable(false)
                ->searchable(false)
                ->toggleable(),


                TextColumn::make('no_ktp')
                    ->label('No. KTP')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->copyable(),

                TextColumn::make('nama')
                    ->label('Nama')
                    ->sortable()
                    ->searchable()
                    ->copyable()
                    ->tooltip(fn ($record) => $record->nama),

                TextColumn::make('alamat')
                    ->label('Alamat 1')
                    ->formatStateUsing(function ($record) {
                        if ($record->alamat === 'Lainnya' && $record->alamat_custom) {
                            return $record->alamat_custom;
                        }
                        return $record->alamat;
                    })
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->alamat === 'Lainnya' ? $record->alamat_custom : $record->alamat),

                TextColumn::make('blok')
                    ->label('Blok')
                    ->toggleable(),

                TextColumn::make('unit')
                    ->label('Unit')
                    ->toggleable(),

                TextColumn::make('tgl_instalasi')
                    ->label('Tanggal Instalasi')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('hargaLayanan.brand')
                    ->label('Brand')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('layanan')
                    ->label('Paket')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('no_telp')
                    ->label('No. Telepon')
                    ->copyable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->sortable()
                    ->searchable()
                    ->copyable()
                    ->toggleable()
                    ->tooltip(fn ($record) => $record->email),
                
                TextColumn::make('alamat_2')
                    ->label('Alamat 2')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('created_at')
                    ->label('Terdaftar Pada')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'asc')
            ->filters([
                SelectFilter::make('alamat')
                    ->label('Filter Alamat')
                    ->options(function () {
                        return Pelanggan::select('alamat')
                            ->distinct()
                            ->get()
                            ->mapWithKeys(function ($item) {
                                $count = Pelanggan::where('alamat', $item->alamat)->count();
                                return [$item->alamat => $item->alamat . ' (' . $count . ' user)'];
                            });
                    })
                    ->indicator('Alamat'),
                
                SelectFilter::make('id_brand')
                    ->label('Filter Brand')
                    ->relationship('hargaLayanan', 'brand')
                    ->indicator('Brand'),
                
                SelectFilter::make('layanan')
                    ->label('Filter Paket')
                    ->options([
                        '10 Mbps' => '10 Mbps',
                        '20 Mbps' => '20 Mbps',
                        '30 Mbps' => '30 Mbps',
                        '50 Mbps' => '50 Mbps',
                    ])
                    ->indicator('Paket'),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Konfirmasi Hapus Data Pelanggan')
                        ->modalDescription('Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.')
                        ->modalSubmitActionLabel('Ya, Hapus')
                        ->modalCancelActionLabel('Batal')
                        ->successNotificationTitle('🗑️ Pelanggan Berhasil Dihapus!')
                        ->after(function () {
                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('🗑️ Data Pelanggan Telah Dihapus!')
                                ->body('Pelanggan ini telah dihapus secara permanen.')
                                ->send();
                        }),
                ])
                ->tooltip('Aksi')
                ->icon('heroicon-m-ellipsis-vertical'),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Data Pelanggan Terpilih')
                    ->modalDescription('Apakah Anda yakin ingin menghapus data yang terpilih? Tindakan ini tidak dapat dibatalkan.'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPelanggans::route('/'),
            'create' => Pages\CreatePelanggan::route('/create'),
            'edit' => Pages\EditPelanggan::route('/{record}/edit'),
        ];
    }
}