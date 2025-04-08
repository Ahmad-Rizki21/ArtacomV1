<?php
namespace App\Filament\Widgets;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use Illuminate\Support\Facades\DB;

class PelangganPerLokasiPaketChart extends ApexChartWidget
{
    protected static ?string $chartId = 'pelangganPerLokasiPaket';
    protected static ?string $heading = 'Pelanggan per Lokasi & Paket';
    
    // Setting tampilan penuh
    protected int | string | array $columnSpan = 'full';
    
    protected function getOptions(): array
    {
        // Definisikan paket-paket
        $paketOptions = ['10 Mbps', '20 Mbps', '30 Mbps', '50 Mbps'];
        
        // Dapatkan semua lokasi dari tabel pelanggan
        $lokasiAll = DB::table('pelanggan')
            ->select('alamat')
            ->whereNotNull('alamat')
            ->where('alamat', '!=', '')
            ->distinct()
            ->orderBy('alamat')
            ->pluck('alamat')
            ->toArray();
            
        // Query data untuk langganan
        $langgananData = DB::table('langganan')
            ->join('pelanggan', 'langganan.pelanggan_id', '=', 'pelanggan.id')
            ->select('pelanggan.alamat', 'langganan.layanan', DB::raw('count(*) as jumlah'))
            ->whereNotNull('pelanggan.alamat')
            ->groupBy('pelanggan.alamat', 'langganan.layanan')
            ->get();
        
        // Inisialisasi data untuk setiap paket
        $seriesData = [];
        foreach ($paketOptions as $paket) {
            $paketData = [];
            foreach ($lokasiAll as $lok) {
                $count = $langgananData->where('alamat', $lok)
                         ->where('layanan', $paket)
                         ->first()?->jumlah ?? 0;
                $paketData[] = $count;
            }
            
            $seriesData[] = [
                'name' => $paket,
                'data' => $paketData,
            ];
        }

        // Hitung total pelanggan
        $totalPelanggan = DB::table('pelanggan')->count();
        
        // Update heading dengan total pelanggan
        self::$heading = "Pelanggan per Lokasi & Paket (Total: {$totalPelanggan})";
        
        // Debugging
        \Illuminate\Support\Facades\Log::info('Chart Data', [
            'lokasi_count' => count($lokasiAll),
            'lokasi' => $lokasiAll,
            'series_count' => count($seriesData),
            'first_series_data' => $seriesData[0]['data'] ?? []
        ]);
        
        return [
            'chart' => [
                'type' => 'bar',
                'height' => 450, // Tingkatkan height
                'width' => '100%',
                'stacked' => true,
                'toolbar' => [
                    'show' => true,
                    'tools' => [
                        'download' => true,
                        'selection' => false,
                        'zoom' => true,
                        'zoomin' => true,
                        'zoomout' => true,
                        'pan' => true,
                    ],
                ],
                'events' => [
                    'click' => 'function(event, chartContext, config) {
                        if (config.dataPointIndex >= 0) {
                            const lokasi = config.w.config.xaxis.categories[config.dataPointIndex];
                            const paket = config.w.config.series[config.seriesIndex].name;
                            const jumlah = config.w.config.series[config.seriesIndex].data[config.dataPointIndex];
                            
                            alert("Lokasi: " + lokasi + "\\nPaket: " + paket + "\\nJumlah pelanggan: " + jumlah);
                        }
                    }',
                ],
            ],
            'series' => $seriesData,
            'xaxis' => [
                'categories' => $lokasiAll,
                'labels' => [
                    'rotate' => -45,
                    'rotateAlways' => true,
                    'hideOverlappingLabels' => false,
                    'trim' => false,
                    'style' => [
                        'fontSize' => '11px',
                    ],
                ],
                'tickPlacement' => 'on',
            ],
            'yaxis' => [
                'min' => 0,
                'forceNiceScale' => true,
                'labels' => [
                    'formatter' => 'function(val) { return Math.floor(val); }',
                ],
            ],
            'colors' => ['#3498db', '#e74c3c', '#2ecc71', '#f39c12'],
            'dataLabels' => [
                'enabled' => true,
                'formatter' => 'function (val) { return val > 0 ? val : ""; }',
                'style' => [
                    'fontSize' => '12px',
                    'colors' => ['#fff'],
                ],
                'dropShadow' => [
                    'enabled' => false,
                ],
            ],
            'plotOptions' => [
                'bar' => [
                    'horizontal' => false,
                    'columnWidth' => '70%',
                    'endingShape' => 'flat',
                    'borderRadius' => 4,
                ],
            ],
            'legend' => [
                'position' => 'bottom',
                'horizontalAlign' => 'center',
                'offsetY' => 10,
            ],
            'tooltip' => [
                'enabled' => true,
                'shared' => true,
                'intersect' => false,
            ],
            'grid' => [
                'show' => true,
                'borderColor' => '#e0e0e0',
                'strokeDashArray' => 0,
                'position' => 'back',
            ],
            'responsive' => [
                [
                    'breakpoint' => 1000,
                    'options' => [
                        'chart' => [
                            'height' => 400
                        ],
                        'legend' => [
                            'position' => 'bottom'
                        ]
                    ]
                ]
            ],
            'states' => [
                'hover' => [
                    'filter' => [
                        'type' => 'darken',
                        'value' => 0.9
                    ]
                ]
            ]
        ];
    }
    
    protected static ?string $pollingInterval = '60s';
    
    public static function getSort(): int
    {
        return 3;
    }
}