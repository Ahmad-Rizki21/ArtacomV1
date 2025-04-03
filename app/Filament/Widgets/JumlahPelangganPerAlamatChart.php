<?php

namespace App\Filament\Widgets;

use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use Illuminate\Support\Facades\DB;

class JumlahPelangganPerAlamatChart extends ApexChartWidget
{
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'jumlahPelangganPerAlamat';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'Jumlah Pelanggan per Rusun/Perumahan';

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     *
     * @return array
     */
    protected function getOptions(): array
    {
        // Definisikan alamat-alamat yang ingin ditampilkan
        $alamatOptions = [
            'Rusun Nagrak',
            'Rusun Pinus Elok',
            'Rusun Pulogebang Tower',
            'Rusun Pulogebang Blok',
            'Rusun KM2',
            'Rusun Tipar Cakung',
            'Rusun Albo',
            'Perumahan Tambun',
            'Perumahan Waringin',
            'Perumahan Parama',
        ];

        // Ambil data jumlah pelanggan per alamat
        $jumlahPelangganPerAlamat = DB::table('pelanggan')
            ->select('alamat', DB::raw('count(*) as jumlah'))
            ->whereIn('alamat', $alamatOptions)
            ->groupBy('alamat')
            ->get();

        // Inisialisasi data untuk chart
        $labels = $alamatOptions;
        $data = array_fill(0, count($alamatOptions), 0);

        // Isi data berdasarkan hasil query
        foreach ($jumlahPelangganPerAlamat as $item) {
            $index = array_search($item->alamat, $labels);
            if ($index !== false) {
                $data[$index] = $item->jumlah;
            }
        }

        // Hitung total pelanggan untuk judul
        $totalPelanggan = array_sum($data);

        // Perbarui heading dengan total jika diperlukan
        self::$heading = "Jumlah Pelanggan per Rusun/Perumahan (Total: {$totalPelanggan})";

        // Pastikan selalu ada data
        if (empty(array_filter($data))) {
            $data = [1]; // Minimal satu titik data
            $labels = ['Tidak Ada Data'];
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
                    'name' => 'Jumlah Pelanggan',
                    'data' => $data,
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
                ],
            ],
            'dataLabels' => [
                'enabled' => true,
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
        return -3;
    }
}