<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Pelanggan;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LaporanPelangganChart extends ChartWidget
{
    protected static ?string $heading = 'Pelanggan Baru per Bulan';

    protected function getData(): array
    {
        // Ambil data pelanggan baru per bulan untuk tahun berjalan
        $pelangganBaru = Pelanggan::select(
            DB::raw('MONTH(created_at) as month'),
            DB::raw('COUNT(*) as count')
        )
        ->whereYear('created_at', now()->year)
        ->groupBy('month')
        ->orderBy('month')
        ->get();

        // Siapkan data untuk chart
        $monthLabels = [
            'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 
            'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
        ];

        // Inisialisasi data dengan 0 untuk setiap bulan
        $data = array_fill(0, 12, 0);

        // Isi data dengan jumlah pelanggan baru
        foreach ($pelangganBaru as $item) {
            $data[$item->month - 1] = $item->count;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Pelanggan Baru',
                    'data' => $data,
                    'backgroundColor' => 'rgba(75, 192, 192, 0.6)',
                    'borderColor' => 'rgba(75, 192, 192, 1)',
                    'fill' => true
                ]
            ],
            'labels' => $monthLabels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}