<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServerMonitoringResource\Pages;
use App\Models\MikrotikServer;
use App\Jobs\MonitorMikrotikServers;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;

class ServerMonitoringResource extends Resource
{
    protected static ?string $model = MikrotikServer::class;
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'Network Management';
    protected static ?string $navigationLabel = 'Server Monitoring';
    protected static ?string $slug = 'server-monitoring';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Server Name')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('host_ip')
                    ->label('Host/IP')
                    ->searchable()
                    ->sortable(),
                
                BadgeColumn::make('last_connection_status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'success' => 'Online',
                        'failed' => 'Offline',
                        default => 'Pending'
                    })
                    ->colors([
                        'success' => 'success',
                        'danger' => 'failed',
                        'warning' => 'pending'
                    ]),
                
                TextColumn::make('last_connected_at')
                    ->label('Last Check')
                    ->dateTime('M j, Y H:i:s')
                    ->sortable(),
                
               // Di dalam ServerMonitoringResource.php (bagian kolom CPU dan Memory)
                TextColumn::make('latestMetric.cpu_load')
                ->label('CPU Load')
                ->numeric(
                    decimalPlaces: 1,
                    decimalSeparator: '.',
                    thousandsSeparator: ','
                )
                ->formatStateUsing(function ($state) {
                    // Pastikan state numeric
                    $value = is_numeric($state) ? (float)$state : 0;
                    return $value > 0 ? number_format($value, 1).'%' : 'N/A';
                })
                ->sortable(),

                TextColumn::make('latestMetric.memory_usage')
                ->label('Memory')
                ->numeric(
                    decimalPlaces: 1,
                    decimalSeparator: '.',
                    thousandsSeparator: ','
                )
                ->formatStateUsing(function ($state) {
                    // Konversi ke float dan validasi
                    $value = is_numeric($state) ? (float)$state : 0;
                    return $value > 0 ? number_format($value, 1).'%' : 'N/A';
                })
                ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'success' => 'Online',
                        'failed' => 'Offline',
                    ])
                    ->attribute('last_connection_status')
                    ->default('success')
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                Action::make('monitor_now')
                    ->label('Check Now')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function (MikrotikServer $record) {
                        MonitorMikrotikServers::dispatch($record);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Monitoring Started')
                            ->body("Checking server {$record->name}")
                            ->success()
                            ->send();
                    }),
                
                Action::make('view_details')
                    ->label('View Details')
                    ->icon('heroicon-o-eye')
                    ->url(fn (MikrotikServer $record) => static::getUrl('view', ['record' => $record]))
            ])
            ->defaultSort('last_connected_at', 'desc')
            ->emptyStateHeading('No servers found')
            ->emptyStateDescription('Create your first server to start monitoring')
            ->paginated([10, 25, 50]);
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServerMonitorings::route('/'),
            'view' => Pages\ViewServerMonitoring::route('/{record}'),
        ];
    }
}