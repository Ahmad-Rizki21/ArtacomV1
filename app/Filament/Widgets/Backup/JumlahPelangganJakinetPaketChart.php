<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class JumlahPelangganJakinetPaketChart extends ChartWidget
{
    protected static ?string $heading = 'Jumlah Pelanggan Jakinet / Paket';

    /**
     * Mendapatkan data untuk chart
     *
     * @return array
     */
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

        // Hitung total pelanggan
        $totalPelanggan = array_sum($data);

        // Update heading dengan total pelanggan
        static::$heading = "Jumlah Pelanggan Jakinet / Paket (Total: {$totalPelanggan})";

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Jumlah Pelanggan Jakinet',
                    'data' => $data,
                    'backgroundColor' => '#3498db', // Warna biru cerah seperti di screenshot
                    'borderColor' => '#3498db',
                    'borderWidth' => 1,
                ]
            ]
        ];
    }

    /**
     * Mendapatkan tipe chart
     *
     * @return string
     */
    protected function getType(): string
    {
        return 'bar';
    }
    
    /**
     * Mendapatkan urutan widget
     *
     * @return int
     */
    public static function getSort(): int
    {
        return -2; // Prioritas ketiga
    }

    /**
     * Mendapatkan opsi konfigurasi chart
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            'maintainAspectRatio' => false,
            'height' => 300,
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'color' => 'rgba(200, 200, 200, 0.2)',
                    ],
                ],
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                ]
            ],
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => '
                            function(context) {
                                var label = context.dataset.label || "";
                                var value = context.parsed.y || 0;
                                return label + ": " + value;
                            }
                        '
                    ]
                ]
            ]
        ];
    }
}