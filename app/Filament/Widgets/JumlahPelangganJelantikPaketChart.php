<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use App\Models\Langganan;

class JumlahPelangganJelantikPaketChart extends ChartWidget
{
    protected static ?string $heading = 'Jumlah Pelanggan Jelantik / Paket';
    
    protected static ?int $sort = 2;
    
    // Menggunakan columnSpan yang sama dengan widget invoice
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $pollingInterval = '60s';

    protected function getViewData(): array
    {
        // Ambil data jumlah pelanggan per paket untuk ditampilkan di footer
        $paketCounts = $this->getPaketCounts();
        $totalPelanggan = array_sum($paketCounts);
        
        $paketText = '';
        foreach ($paketCounts as $paket => $count) {
            $percentage = $totalPelanggan > 0 ? round(($count / $totalPelanggan) * 100) : 0;
            $paketText .= "{$paket}: {$count} ({$percentage}%) | ";
        }
        $paketText = rtrim($paketText, ' | ');
        
        return [
            'paketText' => $paketText,
            'totalPelanggan' => $totalPelanggan,
        ];
    }
    
    // Mendapatkan jumlah pelanggan per paket
    protected function getPaketCounts(): array
    {
        // Definisikan paket-paket 
        $paketOptions = [
            '10 Mbps' => '10 Mbps',
            '20 Mbps' => '20 Mbps',
            '30 Mbps' => '30 Mbps',
            '50 Mbps' => '50 Mbps'
        ];
        
        // Ambil data jumlah pelanggan per paket untuk Jelantik
        $jumlahPelangganPerPaket = DB::table('langganan')
            ->select('layanan', DB::raw('count(*) as jumlah'))
            ->where('id_brand', 'ajn-02')
            // Hapus kondisi whereNull('deleted_at')
            ->groupBy('layanan')
            ->get()
            ->pluck('jumlah', 'layanan')
            ->toArray();
        
        // Buat hasil dengan semua paket, diisi 0 jika tidak ada data
        $result = [];
        foreach ($paketOptions as $key => $label) {
            $result[$label] = $jumlahPelangganPerPaket[$key] ?? 0;
        }
        
        return $result;
    }
    
    protected function getOptions(): array
    {
        return [
            'maintainAspectRatio' => false,
            'height' => 350,
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'color' => 'rgba(200, 200, 200, 0.2)',
                    ],
                ],
                'x' => [
                    'grid' => [
                        'color' => 'rgba(200, 200, 200, 0.2)',
                    ],
                ]
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                    'labels' => [
                        'color' => '#ffffff',
                    ]
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
            // Hapus kondisi whereNull('deleted_at')
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
        
        // Hitung total untuk ditampilkan di heading
        $totalPelanggan = array_sum($data);
        $paketMbps20 = $data[1] ?? 0; // Index 1 seharusnya 20 Mbps
        $persentaseMbps20 = $totalPelanggan > 0 ? round(($paketMbps20 / $totalPelanggan) * 100) : 0;
        
        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Jumlah Pelanggan Jelantik',
                    'data' => $data,
                    'backgroundColor' => '#605ca8', // Warna ungu sesuai screenshot
                    'borderColor' => '#605ca8',
                    'borderWidth' => 1,
                ]
            ]
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
    
    protected function getFooter(): ?string
    {
        $paketCounts = $this->getPaketCounts();
        $totalPelanggan = array_sum($paketCounts);
        
        if ($totalPelanggan === 0) {
            return 'Belum ada pelanggan';
        }
        
        // Buat teks paket dengan badge berwarna
        $paketHtml = '<div class="flex flex-wrap gap-2 justify-center">';
        
        $colors = [
            '10 Mbps' => 'bg-indigo-600',
            '20 Mbps' => 'bg-purple-600',
            '30 Mbps' => 'bg-violet-600',
            '50 Mbps' => 'bg-fuchsia-600',
        ];
        
        foreach ($paketCounts as $paket => $count) {
            if ($count === 0) continue;
            
            $percentage = round(($count / $totalPelanggan) * 100);
            $color = $colors[$paket] ?? 'bg-gray-600';
            
            $paketHtml .= '
                <div class="' . $color . ' text-white text-xs px-2 py-1 rounded">
                    ' . $paket . ': ' . $count . ' (' . $percentage . '%)
                </div>
            ';
        }
        
        $paketHtml .= '</div>';
        
        return $paketHtml;
    }
}