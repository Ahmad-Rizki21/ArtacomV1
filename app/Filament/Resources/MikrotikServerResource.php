<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MikrotikServerResource\Pages;
use App\Models\MikrotikServer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use RouterOS\Client;
use RouterOS\Query;

class MikrotikServerResource extends Resource
{
    protected static ?string $model = MikrotikServer::class;

    protected static ?string $navigationIcon = 'heroicon-o-server';
    
    protected static ?string $navigationGroup = 'Network Management';
    
    protected static ?string $navigationLabel = 'Mikrotik Servers';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Server Details')
                    ->schema([
                        TextInput::make('name')
                            ->label('Server Name')
                            ->required()
                            ->unique(ignorable: fn ($record) => $record)
                            ->placeholder('e.g., Rusun Pinus Server'),
                        
                        TextInput::make('host_ip')
                            ->label('Host/IP Router')
                            ->required()
                            ->rules(['ip'])
                            ->placeholder('192.168.116.3'),
                        
                        TextInput::make('username')
                            ->label('Username')
                            ->required()
                            ->placeholder('admin'),
                        
                        TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->required()
                            ->revealable(),
                        
                        TextInput::make('port')
                            ->label('Port')
                            ->default(8728)
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(65535),
                        
                        // Gunakan Actions untuk test connection
                        Actions::make([
                            Action::make('test_connection')
                                ->label('Test Connection')
                                ->color('primary')
                                ->action(function ($livewire) {
                                    $data = $livewire->form->getState();

                                    try {
                                        $client = new Client([
                                            'host' => $data['host_ip'],
                                            'user' => $data['username'],
                                            'pass' => $data['password'],
                                            'port' => isset($data['port']) ? (int)$data['port'] : 8728,
                                            'timeout' => 10,
                                            'attempts' => 2,
                                        ]);

                                        $query = new Query('/system/resource/print');
                                        $response = $client->query($query)->read();

                                        \Filament\Notifications\Notification::make()
                                            ->title('Koneksi Berhasil')
                                            ->body('Terhubung ke Server Mikrotik')
                                            ->success()
                                            ->send();
                                    } catch (\Exception $e) {
                                        \Filament\Notifications\Notification::make()
                                            ->title('Koneksi Gagal')
                                            ->body($e->getMessage())
                                            ->danger()
                                            ->send();
                                    }
                                })
                        ]),
                        
                        Toggle::make('is_active')
                            ->label('Active Server')
                            ->default(true)
                    ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Server Name')
                    ->searchable()
                    ->sortable(),
                
                // Pilih salah satu solusi di atas
                TextColumn::make('host_ip')
                    ->label('Host/IP:Port')
                    ->formatStateUsing(function ($state, MikrotikServer $record) {
                        return $state.':'.($record->port ?? 8728);
                    })
                    ->searchable(),
                
                BadgeColumn::make('last_connection_status')
                    ->label('Status')
                    ->colors([
                        'success' => 'success',
                        'failed' => 'danger',
                    ]),
                
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
            ])
            ->actions([
                // Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                
                // Action untuk test koneksi di tabel
                Tables\Actions\Action::make('test_connection')
                    ->label('Test Connection')
                    ->icon('heroicon-o-signal')
                    ->color('info')
                    ->action(function (MikrotikServer $record) {
                        try {
                            // Konfigurasi koneksi
                            $client = new Client([
                                'host' => $record->host_ip,
                                'user' => $record->username,
                                'pass' => $record->password,
                                'port' => $record->port ?? 8728,
                                'timeout' => 10,
                                'attempts' => 2
                            ]);

                            // Ambil informasi sistem
                            $query = new Query('/system/resource/print');
                            $response = $client->query($query)->read();

                            // Update status koneksi
                            $record->update([
                                'last_connection_status' => 'success',
                                'last_connected_at' => now(),
                                'ros_version' => $response[0]['version'] ?? null
                            ]);

                            // Notifikasi sukses
                            \Filament\Notifications\Notification::make()
                                ->title('Koneksi Berhasil')
                                ->body('Terhubung ke Server Mikrotik')
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            // Update status koneksi gagal
                            $record->update([
                                'last_connection_status' => 'failed',
                                'last_connected_at' => now()
                            ]);

                            // Notifikasi gagal
                            \Filament\Notifications\Notification::make()
                                ->title('Koneksi Gagal')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMikrotikServers::route('/'),
            'create' => Pages\CreateMikrotikServer::route('/create'),
            'edit' => Pages\EditMikrotikServer::route('/{record}/edit'),
        ];
    }
}