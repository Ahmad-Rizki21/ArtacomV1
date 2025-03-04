<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PelangganResource\Pages;
use App\Models\Pelanggan;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TextArea;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;







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

                TextArea::make('alamat')
                    ->label('Alamat')
                    ->required()
                    ->placeholder('Masukkan Alamat Lengkap'),

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
            ])
            ->filters([]) // Tambahkan filter jika diperlukan
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