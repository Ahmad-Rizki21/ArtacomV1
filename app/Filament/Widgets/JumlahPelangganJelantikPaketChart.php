<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class JumlahPelangganJelantikPaketChart extends ChartWidget
{
    protected static ?string $heading = 'Jumlah Pelanggan Jelantik / Paket';


    protected function getData(): array
    {
        // Definisikan paket-paket 
        $paketOptions = [
            '10 Mbps',
            '20 Mbps',
            '30 Mbps',
            '50 Mbps'
        ];

        // Ambil data jumlah pelanggan per paket untuk Jelantik
        $jumlahPelangganPerPaket = DB::table('langganan')
            ->select('layanan', DB::raw('count(*) as jumlah'))
            ->where('id_brand', 'ajn-02')
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
                    'label' => 'Jumlah Pelanggan Jelantik',
                    'data' => $data,
                    'backgroundColor' => 'rgb(103, 93, 183)', // Ungu
                    'borderColor' => 'rgb(103, 93, 183)',
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
        return -2; // Prioritas keempat
    }
}