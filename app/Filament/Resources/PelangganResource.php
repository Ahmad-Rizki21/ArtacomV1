<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PelangganResource\Pages;
use App\Models\Pelanggan;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TextArea;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Testing\TestsColumns;







class PelangganResource extends Resource
{
    protected static ?string $model = Pelanggan::class;

    
    protected static ?string $navigationLabel = 'Data Pelanggan';
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'FTTH'; // Mengelompokkan dalam grup "FTTH"


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('no_ktp')
                    ->label('No. KTP')
                    ->required()
                    ->maxLength(16)
                    ->unique(ignoreRecord: true)
                    ->numeric()
                    ->placeholder('Masukkan No. KTP (16 digit)'),

                TextInput::make('nama')
                    ->label('Nama Lengkap')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Masukkan Nama Lengkap'),

                    // Alamat 1 (Dropdown untuk Rusun + Text Input jika tidak ada di dropdown)
                Select::make('alamat')
                ->label('Alamat (Rusun)')
                ->options([
                    'Rusun Nagrak' => 'Rusun Nagrak',
                    'Rusun Pinus Elok' => 'Rusun Pinus Elok',
                    'Rusun Pulogebang Tower' => 'Rusun Pulogebang Tower',
                    'Rusun Pulogebang Blok' => 'Rusun Pulogebang Blok',
                    'Rusun KM2' => 'Rusun KM2',
                    'Rusun Tipar Cakung' => 'Rusun Tipar Cakung',
                    'Rusun Albo' => 'Rusun Albo',
                    'Perumahan Waringin' => 'Perumahan Waringin Kurung',
                    'Perumahan Parama' => 'Perumahan Parama Serang',
                    'Lainnya' => 'Lainnya' // Pilihan untuk input manual
                ])
                ->placeholder('Pilih Rusun')
                ->helperText('Pilih lokasi rusun atau pilih "Lainnya" jika tidak ada')
                ->reactive() // Membuat dropdown reaktif terhadap perubahan
                ->afterStateUpdated(function ($state, callable $set) {
                    // Menampilkan kolom input teks jika memilih "Lainnya"
                    if ($state === 'Lainnya') {
                        $set('alamat_custom', ''); // Mengosongkan input teks jika memilih "Lainnya"
                    }
                }),

                // Alamat 1 Custom (Field teks jika "Lainnya" dipilih di dropdown)
                TextInput::make('alamat_custom')
                ->label('Alamat Lainnya')
                ->placeholder('Masukkan alamat rusun lainnya')
                ->nullable()  // Menjadi opsional
                ->helperText('Masukkan alamat lain jika pilihan rusun tidak tersedia')
                ->visible(fn ($get) => $get('alamat') === 'Lainnya'), // Menampilkan input ini jika "Lainnya" dipilih

                // Alamat 2 (Teks untuk alamat lain)
                TextArea::make('alamat_2')
                    ->label('Alamat Lain (Opsional)')
                    ->nullable()
                    ->placeholder('Masukkan Alamat Lain (Opsional)')
                    ->helperText('Masukkan alamat tambahan jika ada'),

                TextInput::make('blok')
                    ->label('Blok')
                    ->required()
                    ->maxLength(255),

                TextInput::make('unit')
                    ->label('Unit')
                    ->required()
                    ->maxLength(255),

                TextInput::make('no_telp')
                    ->label('No. Telepon')
                    ->required()
                    ->maxLength(15)
                    ->tel()
                    ->placeholder('Masukkan No. Telepon'),

                TextInput::make('email')
                    ->label('Email')
                    ->required()
                    ->email()
                    ->maxLength(255)
                    ->placeholder('Masukkan Email Aktif'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no_ktp')
                    ->label('No. KTP')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('nama')
                    ->label('Nama')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('alamat')
                    ->label('Alamat')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->alamat), // Tooltip untuk alamat panjang

                TextColumn::make('alamat_2')
                    ->label('Alamat Lain')
                    ->limit(50)
                    ->default('N/A')
                    ->tooltip(fn ($record) => $record->alamat_2),

                TextColumn::make('blok')
                    ->label('Blok'),

                TextColumn::make('unit')
                    ->label('Unit'),

                TextColumn::make('no_telp')
                    ->label('No. Telepon'),

                TextColumn::make('email')
                    ->label('Email')
                    ->sortable()
                    ->searchable(),
                
                // Menambahkan kolom untuk menunjukkan jumlah pengguna berdasarkan alamat
                    // TextColumn::make('jumlah_pelanggan')
                    // ->label('Jumlah Pelanggan')
                    // ->formatStateUsing(function ($record) {
                    //     // Query untuk menghitung jumlah pelanggan berdasarkan alamat
                    //     return Pelanggan::where('alamat', $record->alamat)->count();
                    // })
                    // ->sortable(),
    
            ])

            // Filter untuk Alamat (Lokasi)
            ->filters([
                SelectFilter::make('alamat')  // Membuat filter berdasarkan alamat
                    ->label('Filter Alamat')
                    ->options(function () {
                        // Ambil alamat yang unik dari tabel Pelanggan dan hitung jumlah pelanggan di setiap alamat
                        return Pelanggan::select('alamat')
                            ->distinct()  // Hanya menampilkan alamat yang unik
                            ->get()
                            ->mapWithKeys(function ($item) {
                                // Menghitung jumlah pelanggan untuk setiap alamat
                                $count = Pelanggan::where('alamat', $item->alamat)->count();
                                return [$item->alamat => $item->alamat . ' (' . $count . ' user)'];
                            });
                    })
            ])


            ->actions([
                EditAction::make(),
                // DeleteAction::make(),
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
            ->bulkActions([
                DeleteBulkAction::make(),
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