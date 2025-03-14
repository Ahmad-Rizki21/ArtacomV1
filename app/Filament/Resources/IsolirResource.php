<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IsolirResource\Pages;
use App\Models\Isolir;
use App\Models\Langganan;
use App\Models\MikrotikServer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn; // Ini akan perlu diganti
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class IsolirResource extends Resource
{
    protected static ?string $model = Isolir::class;

    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';
    
    protected static ?string $navigationGroup = 'Network Management';

    
    protected static ?string $navigationLabel = 'Daftar Isolir';
    
    protected static ?string $modelLabel = 'Isolir';
    
    protected static ?string $pluralModelLabel = 'Isolir';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Detail Isolir')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('langganan_id')
                                ->label('Pilih Langganan')
                                ->options(
                                    Langganan::with('pelanggan')
                                        ->where('user_status', 'Aktif')
                                        ->get()
                                        ->mapWithKeys(function ($langganan) {
                                            return [
                                                $langganan->id => 
                                                "{$langganan->pelanggan->nama} - {$langganan->id_brand} - {$langganan->layanan}"
                                            ];
                                        })
                                )
                                ->searchable()
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function($set, $state) {
                                    $langganan = Langganan::find($state);
                                    if ($langganan) {
                                        $set('pelanggan_id', $langganan->pelanggan_id);
                                        $set('brand', $langganan->id_brand);
                                        $set('id_pelanggan', $langganan->id_pelanggan);
                                        $set('profile_pppoe', $langganan->profile_pppoe);
                                        $set('olt', $langganan->olt);
                                    }
                                }),

                            Select::make('status_isolir')
                                ->label('Status Isolir')
                                ->options([
                                    'pending' => 'Pending',
                                    'aktif' => 'Aktif',
                                    'selesai' => 'Selesai'
                                ])
                                ->default('pending')
                                ->required(),
                        ]),

                        Grid::make(2)->schema([
                            Select::make('olt')
                                ->label('Server Mikrotik')
                                ->options(
                                    MikrotikServer::active()->pluck('name', 'name')
                                )
                                ->searchable()
                                ->required(),

                            Forms\Components\TextInput::make('id_pelanggan')
                                ->label('PPPoE Name')
                                ->disabled(),
                        ]),

                        Textarea::make('alasan_isolir')
                            ->label('Alasan Isolir')
                            ->rows(3)
                            ->required(),

                        Grid::make(2)->schema([
                            DateTimePicker::make('tanggal_isolir')
                                ->label('Tanggal Isolir')
                                ->default(now())
                                ->required(),

                            DateTimePicker::make('tanggal_aktif_kembali')
                                ->label('Tanggal Aktif Kembali')
                                ->nullable(),
                        ]),

                        Textarea::make('catatan')
                            ->label('Catatan Tambahan')
                            ->rows(2)
                            ->nullable()
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('pelanggan.nama')
                    ->label('Nama Pelanggan')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('id_pelanggan')
                    ->label('PPPoE Name')
                    ->searchable(),

                // Solusi 1: Menggunakan TextColumn dengan badge (untuk Filament v3)
                TextColumn::make('brand')
                    ->label('Brand')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ajn-01' => 'primary',
                        'ajn-02' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'ajn-01' => 'Jakinet',
                        'ajn-02' => 'Jelantik',
                        default => $state,
                    }),

                // Solusi 1: Menggunakan TextColumn dengan badge (untuk Filament v3)
                TextColumn::make('status_isolir')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'aktif' => 'danger',
                        'selesai' => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('alasan_isolir')
                    ->label('Alasan')
                    ->limit(30),

                TextColumn::make('tanggal_isolir')
                    ->label('Tanggal Isolir')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('tanggal_aktif_kembali')
                    ->label('Tanggal Aktif')
                    ->dateTime()
                    ->sortable()
                    ->default('-')
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status_isolir')
                    ->options([
                        'pending' => 'Pending',
                        'aktif' => 'Aktif', 
                        'selesai' => 'Selesai'
                    ])
                    ->label('Status Isolir'),

                Tables\Filters\SelectFilter::make('brand')
                    ->options([
                        'ajn-01' => 'Jakinet',
                        'ajn-02' => 'Jelantik'
                    ])
                    ->label('Brand')
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Action::make('detail')
                    ->label('Detail')
                    ->color('info')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Detail Isolir')
                    ->modalContent(function(Isolir $record) {
                        return view('filament.resources.isolir.detail', ['record' => $record]);
                    }),

                Action::make('aktifkan')
                    ->label('Aktifkan')
                    ->color('success')
                    ->icon('heroicon-o-lock-open')
                    ->visible(fn (Isolir $record) => $record->status_isolir !== 'selesai')
                    ->requiresConfirmation()
                    ->action(function (Isolir $record) {
                        try {
                            // Proses aktivasi kembali
                            $result = $record->aktivasiKembali();

                            if ($result) {
                                Notification::make()
                                    ->title('Berhasil Diaktifkan')
                                    ->body("Langganan {$record->pelanggan->nama} telah diaktifkan")
                                    ->success()
                                    ->send();

                                Log::info('Langganan diaktifkan dari status isolir', [
                                    'isolir_id' => $record->id,
                                    'pelanggan_id' => $record->pelanggan_id
                                ]);
                            } else {
                                Notification::make()
                                    ->title('Gagal Mengaktifkan')
                                    ->body('Proses aktivasi gagal. Silakan cek log.')
                                    ->danger()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal Mengaktifkan')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();

                            Log::error('Gagal mengaktifkan langganan dari isolir', [
                                'isolir_id' => $record->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    })
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                
                // Bulk action untuk mengaktifkan kembali
                Tables\Actions\BulkAction::make('bulk_aktifkan')
                    ->label('Aktifkan Terpilih')
                    ->color('success')
                    ->icon('heroicon-o-lock-open')
                    ->action(function ($records) {
                        $aktivasiCount = 0;
                        $failedCount = 0;

                        foreach ($records as $record) {
                            try {
                                $result = $record->aktivasiKembali();
                                if ($result) {
                                    $aktivasiCount++;
                                } else {
                                    $failedCount++;
                                }
                            } catch (\Exception $e) {
                                $failedCount++;
                                Log::error('Gagal mengaktifkan langganan dari isolir', [
                                    'isolir_id' => $record->id,
                                    'error' => $e->getMessage()
                                ]);
                            }
                        }

                        Notification::make()
                            ->title('Proses Aktivasi Selesai')
                            ->body("Berhasil diaktifkan: $aktivasiCount, Gagal: $failedCount")
                            ->when($failedCount > 0, fn($notification) => $notification->danger(), fn($notification) => $notification->success())
                            ->send();
                    })
                    ->requiresConfirmation()
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIsolirs::route('/'),
            'create' => Pages\CreateIsolir::route('/create'),
            'edit' => Pages\EditIsolir::route('/{record}/edit'),
        ];
    }
}