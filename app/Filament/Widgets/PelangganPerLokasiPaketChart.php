<?php
namespace App\Filament\Widgets;

use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Log;

class PelangganPerLokasiPaketChart extends ApexChartWidget
{
    protected static ?string $chartId = 'pelangganPerLokasiPaket';
    protected static ?string $heading = 'Pelanggan per Lokasi & Paket';
    
    protected int | string | array $columnSpan = 'full';
    
    protected function getFilterFormSchema(): array
    {
        try {
            $lokasiOptions = DB::table('pelanggan')
                ->select('alamat')
                ->whereNotNull('alamat')
                ->where('alamat', '!=', '')
                ->distinct()
                ->orderBy('alamat')
                ->pluck('alamat', 'alamat')
                ->toArray();
                
            return [
                Select::make('lokasi_filter')
                    ->label('Filter Lokasi')
                    ->placeholder('Semua Lokasi')
                    ->options($lokasiOptions)
                    ->reactive(),
                Select::make('limit')
                    ->label('Jumlah Lokasi')
                    ->options([
                        5 => '5',
                        10 => '10',
                        20 => '20',
                        100 => 'Semua'
                    ])
                    ->default(10)
                    ->reactive(),
            ];
        } catch (\Exception $e) {
            Log::error('Error in filter schema: ' . $e->getMessage());
            return [];
        }
    }
    
    protected function getOptions(): array
    {
        try {
            // Versi paling sederhana hanya menampilkan chart basic
            
            // Definisi paket
            $paketOptions = ['10 Mbps', '20 Mbps', '30 Mbps', '50 Mbps'];
            
            // Query lokasi
            $lokasiQuery = DB::table('pelanggan')
                ->select('alamat')
                ->whereNotNull('alamat')
                ->where('alamat', '!=', '');
                
            if (!empty($this->filterFormData['lokasi_filter'])) {
                $lokasiQuery->where('alamat', $this->filterFormData['lokasi_filter']);
            }
            
            $lokasiQuery = $lokasiQuery->distinct()->orderBy('alamat');
            
            if (!empty($this->filterFormData['limit']) && $this->filterFormData['limit'] < 100) {
                $lokasiQuery = $lokasiQuery->limit((int)$this->filterFormData['limit']);
            }
            
            $lokasiAll = $lokasiQuery->pluck('alamat')->toArray();
            
            if (empty($lokasiAll)) {
                $lokasiAll = ['No Data'];
            }
            
            // Query jumlah pelanggan per lokasi dan paket
            $langgananData = DB::table('langganan')
                ->join('pelanggan', 'langganan.pelanggan_id', '=', 'pelanggan.id')
                ->select('pelanggan.alamat', 'langganan.layanan', DB::raw('count(*) as jumlah'))
                ->whereNotNull('pelanggan.alamat')
                ->whereIn('pelanggan.alamat', $lokasiAll)
                ->groupBy('pelanggan.alamat', 'langganan.layanan')
                ->get();
            
            // Siapkan data series
            $seriesData = [];
            
            foreach ($paketOptions as $paket) {
                $paketData = [];
                
                foreach ($lokasiAll as $lokasi) {
                    $jumlah = 0;
                    
                    foreach ($langgananData as $item) {
                        if ($item->alamat == $lokasi && $item->layanan == $paket) {
                            $jumlah = (int)$item->jumlah;
                            break;
                        }
                    }
                    
                    $paketData[] = $jumlah;
                }
                
                $seriesData[] = [
                    'name' => $paket,
                    'data' => $paketData,
                ];
            }
            
            // Hitung total pelanggan
            $totalPelanggan = array_sum(array_map(function($series) {
                return array_sum($series['data']);
            }, $seriesData));
            
            self::$heading = "Pelanggan per Lokasi & Paket (Total: {$totalPelanggan})";
            
            // Config paling sederhana untuk ApexCharts
            return [
                'chart' => [
                    'type' => 'bar',
                    'height' => 450,
                ],
                'plotOptions' => [
                    'bar' => [
                        'horizontal' => false,
                    ],
                ],
                'series' => $seriesData,
                'xaxis' => [
                    'categories' => $lokasiAll,
                ],
            ];
            
        } catch (\Exception $e) {
            Log::error('Error in chart: ' . $e->getMessage());
            
            // Return minimal chart bila error
            return [
                'chart' => [
                    'type' => 'bar',
                    'height' => 300,
                ],
                'series' => [
                    [
                        'name' => 'Error',
                        'data' => [1],
                    ],
                ],
                'xaxis' => [
                    'categories' => ['Error: ' . substr($e->getMessage(), 0, 50)],
                ],
            ];
        }
    }
    
    protected static ?string $pollingInterval = null;
    
    public static function getSort(): int
    {
        return 3;
    }
}