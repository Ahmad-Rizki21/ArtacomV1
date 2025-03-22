<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class JumlahPelangganPerAlamatChart extends ChartWidget
{
    protected static ?string $heading = 'Jumlah Pelanggan per Rusun/Perumahan';
    


    protected function getData(): array
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
        if ($totalPelanggan > 0) {
            self::$heading = "Jumlah Pelanggan per Rusun/Perumahan (Total: {$totalPelanggan})";
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Jumlah Pelanggan',
                    'data' => $data,
                    'backgroundColor' => 'rgb(222, 34, 188)', // Pink
                    'borderColor' => 'rgb(222, 34, 188)',
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
        return -3; // Prioritas kedua
    }
}