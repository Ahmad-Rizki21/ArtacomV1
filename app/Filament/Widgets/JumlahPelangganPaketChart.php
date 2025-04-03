<?php

namespace App\Filament\Widgets;

use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use Illuminate\Support\Facades\DB;

class JumlahPelangganPaketChart extends ApexChartWidget
{
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'jumlahPelangganPaket';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'Jumlah Pelanggan per Paket';

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     *
     * @return array
     */
    protected function getOptions(): array
    {
        // Definisikan paket-paket 
        $paketOptions = [
            '10 Mbps',
            '20 Mbps',
            '30 Mbps',
            '50 Mbps'
        ];

        // Ambil data jumlah pelanggan per paket untuk semua brand
        $jumlahPelangganPerPaket = DB::table('langganan')
            ->select('layanan', 'id_brand', DB::raw('count(*) as jumlah'))
            ->whereIn('id_brand', ['ajn-01', 'ajn-02', 'ajn-03'])
            ->groupBy('layanan', 'id_brand')
            ->get()
            ->groupBy('layanan');

        // Inisialisasi data untuk chart
        $labels = $paketOptions;
        $jakinetData = array_fill(0, count($paketOptions), 0);
        $jelantikData = array_fill(0, count($paketOptions), 0);
        $jelantikNagrakData = array_fill(0, count($paketOptions), 0);

        // Isi data berdasarkan hasil query
        foreach ($jumlahPelangganPerPaket as $layanan => $brands) {
            $index = array_search($layanan, $labels);
            if ($index !== false) {
                foreach ($brands as $item) {
                    if ($item->id_brand === 'ajn-01') {
                        $jakinetData[$index] = $item->jumlah;
                    } elseif ($item->id_brand === 'ajn-02') {
                        $jelantikData[$index] = $item->jumlah;
                    } elseif ($item->id_brand === 'ajn-03') {
                        $jelantikNagrakData[$index] = $item->jumlah;
                    }
                }
            }
        }

        // Hitung total pelanggan
        $totalJakinet = array_sum($jakinetData);
        $totalJelantik = array_sum($jelantikData);
        $totalJelantikNagrak = array_sum($jelantikNagrakData);
        $totalPelanggan = $totalJakinet + $totalJelantik + $totalJelantikNagrak;

        // Perbarui heading dengan total pelanggan
        self::$heading = "Jumlah Pelanggan per Paket (Total: {$totalPelanggan})";

        // Pastikan selalu ada data
        if (empty(array_filter($jakinetData)) && 
            empty(array_filter($jelantikData)) && 
            empty(array_filter($jelantikNagrakData))) {
            $jakinetData = [0, 0, 0, 0];
            $jelantikData = [0, 0, 0, 0];
            $jelantikNagrakData = [0, 0, 0, 0];
        }

        return [
            'chart' => [
                'type' => 'bar',
                'height' => 350,
                'width' => '100%',
                'toolbar' => [
                    'show' => true,
                ],
            ],
            'series' => [
                [
                    'name' => 'Jakinet',
                    'data' => $jakinetData,
                    'color' => '#3498db', // Warna biru
                ],
                [
                    'name' => 'Jelantik',
                    'data' => $jelantikData,
                    'color' => 'black', // Warna hitam
                ],
                [
                    'name' => 'Jelantik Nagrak',
                    'data' => $jelantikNagrakData,
                    'color' => 'gray', // Warna abu-abu
                ]
            ],
            'xaxis' => [
                'categories' => $labels,
                'labels' => [
                    'rotate' => -45,
                    'rotateAlways' => true,
                    'hideOverlappingLabels' => true,
                ],
            ],
            'plotOptions' => [
                'bar' => [
                    'horizontal' => false,
                    'columnWidth' => '50%', // Lebar kolom
                    'distributed' => true, // Agar warna berbeda
                ],
            ],
            'dataLabels' => [
                'enabled' => true,
            ],
            'tooltip' => [
                'shared' => true,
                'intersect' => false,
            ],
        ];
    }

    /**
     * Polling interval
     *
     * @var string|null
     */
    protected static ?string $pollingInterval = '60s';
    
    /**
     * Widget sort order
     *
     * @return int
     */
    public static function getSort(): int
    {
        return 2;
    }
}