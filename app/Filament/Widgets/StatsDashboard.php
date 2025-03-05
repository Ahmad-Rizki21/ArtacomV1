<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsDashboard extends BaseWidget
{
    protected function getStats(): array
    {
        //Buat Jumlah Data Pelanggan
        $countPelanggan = \App\Models\Pelanggan::count();

        //Buat Jumlah Data Berlangganan / Mpbs
        $countLangganan = \App\Models\Langganan::count();

        //Buat Jumlah Data Berlangganan / Mpbs
        $countDataTeknis = \App\Models\DataTeknis::count();

        return [
            Stat::make('Jumlah Data Pelanggan', $countPelanggan . ' Pelanggan')
            ->icon('heroicon-o-cog')
            ->color('primary'),
            Stat::make('Jumlah Berlangganan / Mbps', $countLangganan . ' User / Mbps')
            ->icon('heroicon-o-users')
            ->color('success'),
            Stat::make('Jumlah Data Teknis / User', $countDataTeknis . ' Data Teknis / User')
            ->icon('heroicon-o-signal')
            ->color('warning'),
        ];
    }

    

    public static function getSort(): int
    {
        return -2; // Membuat widget ini memiliki prioritas tinggi
    }
}
