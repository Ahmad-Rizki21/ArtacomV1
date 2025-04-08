<?php

namespace App\Filament\Widgets;

use App\Models\Pelanggan;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Support\Colors\Color;
use Illuminate\Database\Eloquent\Builder;

class PelangganList extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 6;

    public function table(Table $table): Table 
    {
        return $table
            ->query(Pelanggan::query())
            ->columns([
                TextColumn::make('id')
                    ->label('No.')
                    ->numeric()
                    ->sortable()
                    ->alignment('center')
                    ->badge()
                    ->color(Color::Blue),
                
                TextColumn::make('nama')
                    ->label('Nama Pelanggan')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-user'),
                
                TextColumn::make('no_telp')
                    ->label('Kontak')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-device-phone-mobile')
                    ->color(Color::Green),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->color(Color::Indigo)
                    ->icon('heroicon-o-envelope')
                    ->copyable()
                    ->copyMessage('Email copied!')
                    ->copyMessageDuration(1500),
                
                TextColumn::make('alamat')
                    ->label('Alamat Utama')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->icon('heroicon-o-map-pin')
                    ->color(Color::Gray),

                TextColumn::make('alamat_2')
                    ->label('Alamat Tambahan')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->icon('heroicon-o-map')
                    ->color(Color::Gray),
                    
                TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->date('d M Y')
                    ->sortable()
                    ->color(Color::Indigo)
                    ->icon('heroicon-o-calendar-days')
            ])
            ->filters([
                // Filter berdasarkan alamat
                SelectFilter::make('alamat')
                    ->label('Filter Alamat')
                    ->options([
                        'Rusun Nagrak' => 'Rusun Nagrak',
                        'Rusun Pinus Elok' => 'Rusun Pinus Elok',
                        'Rusun Pulogebang Tower' => 'Rusun Pulogebang Tower',
                        'Rusun Pulogebang Blok' => 'Rusun Pulogebang Blok',
                        'Rusun KM2' => 'Rusun KM2',
                        'Rusun Tipar Cakung' => 'Rusun Tipar Cakung',
                        'Rusun Albo' => 'Rusun Albo',
                        'Perumahan Tambun' => 'Perumahan Tambun',
                        'Perumahan Waringin' => 'Perumahan Waringin',
                        'Perumahan Parama' => 'Perumahan Parama',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['value'],
                                fn (Builder $query, $alamat): Builder => $query->where('alamat', $alamat)
                            );
                    }),

                // Filter berdasarkan rentang waktu pendaftaran
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')->label('Terdaftar Dari'),
                        DatePicker::make('created_until')->label('Terdaftar Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date)
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date)
                            );
                    })
            ])
            ->modifyQueryUsing(function (Builder $query) {
                // Tambahkan total user yang sesuai dengan filter
                $totalUsers = $query->count();
                
                // Tambahkan total user ke dalam session atau state
                session()->put('filtered_users_count', $totalUsers);
                
                return $query;
            })
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->striped()
            ->searchable()
            ->headerActions([])
            ->emptyStateHeading('Belum Ada Pelanggan')
            ->emptyStateDescription('Daftar pelanggan akan muncul di sini ketika sudah ada data.')
            ->headerActions([
                // Tampilkan total user yang difilter
                Tables\Actions\Action::make('total_users')
                    ->label(function () {
                        $totalUsers = session('filtered_users_count', 0);
                        return "Total User: {$totalUsers}";
                    })
                    ->color('primary')
                    ->disabled()
            ]);
    }
}

// Optional: Tambahkan widget statistik singkat
class PelangganStatWidget extends \Filament\Widgets\StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            \Filament\Widgets\StatsOverviewWidget\Stat::make('Total Pelanggan', Pelanggan::count())
                ->icon('heroicon-o-user-group')
                ->color('primary'),
            
            \Filament\Widgets\StatsOverviewWidget\Stat::make('Pelanggan Baru Bulan Ini', 
                Pelanggan::whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count()
            )
                ->icon('heroicon-o-arrow-trending-up')
                ->color('success'),
        ];
    }
}