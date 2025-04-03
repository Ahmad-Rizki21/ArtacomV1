<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Filament\Support\Colors\Color;

class JumlahPelangganJelantikPaketChart extends ChartWidget
{
    protected static ?string $heading = 'Jumlah Pelanggan Jelantik / Paket';
    
    protected static ?int $sort = 2;
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $pollingInterval = '60s';

    /**
     * Mendapatkan jumlah pelanggan per paket
     *
     * @return array
     */
    protected function getPaketCounts(): array
    {
        // Definisikan paket-paket dengan warna dan urutan
        $paketOptions = [
            '10 Mbps' => [
                'label' => '10 Mbps',
                'color' => Color::Indigo[500],
                'order' => 1
            ],
            '20 Mbps' => [
                'label' => '20 Mbps',
                'color' => Color::Purple[500],
                'order' => 2
            ],
            '30 Mbps' => [
                'label' => '30 Mbps',
                'color' => Color::Violet[500],
                'order' => 3
            ],
            '50 Mbps' => [
                'label' => '50 Mbps',
                'color' => Color::Fuchsia[500],
                'order' => 4
            ]
        ];
        
        // Ambil data jumlah pelanggan per paket untuk Jelantik
        $jumlahPelangganPerPaket = DB::table('langganan')
            ->select('layanan', DB::raw('count(*) as jumlah'))
            ->where('id_brand', 'ajn-02')
            ->groupBy('layanan')
            ->get()
            ->pluck('jumlah', 'layanan')
            ->toArray();
        
        // Buat hasil dengan semua paket, diisi 0 jika tidak ada data
        $result = [];
        foreach ($paketOptions as $key => $details) {
            $result[$details['label']] = [
                'count' => $jumlahPelangganPerPaket[$key] ?? 0,
                'color' => $details['color'],
                'order' => $details['order']
            ];
        }
        
        // Urutkan berdasarkan 'order'
        uasort($result, function($a, $b) {
            return $a['order'] <=> $b['order'];
        });
        
        return $result;
    }
    
    /**
     * Mendapatkan opsi chart
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'layout' => [
                'padding' => [
                    'top' => 10,
                    'bottom' => 10,
                ]
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'color' => 'rgba(200, 200, 200, 0.2)',
                        'drawBorder' => false,
                    ],
                    'ticks' => [
                        'precision' => 0,
                        'color' => 'rgba(0, 0, 0, 0.7)',
                    ]
                ],
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                    'ticks' => [
                        'color' => 'rgba(0, 0, 0, 0.7)',
                    ]
                ]
            ],
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
                'tooltip' => [
                    'backgroundColor' => 'rgba(0,0,0,0.8)',
                    'titleColor' => 'white',
                    'bodyColor' => 'white',
                    'callbacks' => [
                        'label' => '
                            function(context) {
                                var label = context.dataset.label || "";
                                var value = context.parsed.y || 0;
                                return label + ": " + value + " pelanggan";
                            }
                        '
                    ]
                ]
            ],
            'animation' => [
                'duration' => 1000,
                'easing' => 'easeOutQuart'
            ]
        ];
    }

    /**
     * Mendapatkan data untuk chart
     *
     * @return array
     */
    protected function getData(): array
    {
        $paketCounts = $this->getPaketCounts();
        
        // Persiapkan data untuk chart
        $labels = [];
        $data = [];
        $backgroundColor = [];
        
        foreach ($paketCounts as $paket => $details) {
            $labels[] = $paket;
            $data[] = $details['count'];
            $backgroundColor[] = $details['color'];
        }
        
        // Hitung total pelanggan
        $totalPelanggan = array_sum($data);
        
        // Update heading dengan total pelanggan
        static::$heading = "Jumlah Pelanggan Jelantik / Paket (Total: {$totalPelanggan})";
        
        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Jumlah Pelanggan Jelantik',
                    'data' => $data,
                    'backgroundColor' => $backgroundColor,
                    'borderColor' => $backgroundColor,
                    'borderWidth' => 1,
                    'borderRadius' => 5,
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
     * Mendapatkan footer untuk menampilkan detail paket
     *
     * @return string|null
     */
    protected function getFooter(): ?string
    {
        $paketCounts = $this->getPaketCounts();
        $totalPelanggan = array_sum(array_column($paketCounts, 'count'));
        
        if ($totalPelanggan === 0) {
            return 'Belum ada pelanggan';
        }
        
        // Buat teks paket dengan badge berwarna
        $paketHtml = '<div class="flex flex-wrap gap-2 justify-center">';
        
        foreach ($paketCounts as $paket => $details) {
            $count = $details['count'];
            $color = $details['color'];
            
            if ($count === 0) continue;
            
            $percentage = round(($count / $totalPelanggan) * 100);
            
            $paketHtml .= '
                <div class="text-white text-xs px-2 py-1 rounded" style="background-color: ' . $color . ';">
                    ' . $paket . ': ' . $count . ' (' . $percentage . '%)
                </div>
            ';
        }
        
        $paketHtml .= '</div>';
        
        return $paketHtml;
    }
}