<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class JumlahPelangganJakinetPaketChart extends ChartWidget
{
    protected static ?string $heading = 'Jumlah Pelanggan Jakinet / Paket';
    protected function getData(): array
    {
        // Definisikan paket-paket 
        $paketOptions = [
            '10 Mbps',
            '20 Mbps',
            '30 Mbps',
            '50 Mbps'
        ];

        // Ambil data jumlah pelanggan per paket untuk Jakinet
        $jumlahPelangganPerPaket = DB::table('langganan')
            ->select('layanan', DB::raw('count(*) as jumlah'))
            ->where('id_brand', 'ajn-01')
            ->groupBy('layanan')
            ->get();

        // Inisialisasi data untuk chart
        $labels = $paketOptions;
        $data = array_fill(0, count($paketOptions), 0);

        // Isi data berdasarkan hasil query
        foreach ($jumlahPelangganPerPaket as $item) {
            $index = array_search($item->layanan, $labels);
            if ($index !== false) {
                $data[$index] = $item->jumlah;
            }
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Jumlah Pelanggan Jakinet',
                    'data' => $data,
                    'backgroundColor' => 'rgb(72, 187, 177)', // Hijau kebiruan
                    'borderColor' => 'rgb(72, 187, 177)',
                    'fill' => true
                ]
            ]
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
    
    public static function getSort(): int
    {
        return -2; // Prioritas ketiga
    }
}