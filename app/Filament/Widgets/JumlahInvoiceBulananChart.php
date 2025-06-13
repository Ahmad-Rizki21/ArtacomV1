<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Invoice;

class JumlahInvoiceBulananChart extends ChartWidget
{
    protected static ?string $heading = 'Jumlah Invoice Setiap Bulannya';
    
    protected static ?int $sort = 3;
    // Gunakan columnSpan untuk mengatur lebar
    protected int | string | array $columnSpan = 'full';

    
    protected static ?string $pollingInterval = '60s';
    
    // Property untuk mengetahui tampilan chart yang dipilih (bulanan atau status)
    protected static string $chartView = 'monthly'; // Nilai default: 'monthly' atau 'status'

    // Mengatur tampilan chart
    protected function getViewData(): array
    {
        // Ambil data status invoice untuk ditampilkan di card footer
        $statusCounts = $this->getStatusCounts();
        $totalInvoices = array_sum($statusCounts);
        
        $statusText = '';
        foreach ($statusCounts as $status => $count) {
            $percentage = $totalInvoices > 0 ? round(($count / $totalInvoices) * 100) : 0;
            $statusText .= "{$status}: {$count} ({$percentage}%) | ";
        }
        $statusText = rtrim($statusText, ' | ');
        
        return [
            'statusText' => $statusText,
        ];
    }
    
    // Mendapatkan jumlah invoice per status
    protected function getStatusCounts(): array
    {
        // Ambil status valid dari model Invoice
        $validStatuses = Invoice::VALID_STATUSES;
        
        // Ambil jumlah invoice per status
        $statusCounts = DB::table('invoices')
            ->select('status_invoice', DB::raw('COUNT(*) as total'))
            ->whereNull('deleted_at')
            ->groupBy('status_invoice')
            ->get()
            ->pluck('total', 'status_invoice')
            ->toArray();
            
        // Filter hanya status yang valid
        $filteredCounts = [];
        foreach ($validStatuses as $status) {
            $filteredCounts[$status] = $statusCounts[$status] ?? 0;
        }
        
        return $filteredCounts;
    }
    
    // Mengatur tinggi chart
   protected function getOptions(): array
{
    $isDarkMode = config('filament.layout.theme') === 'dark';

    $lineColor = $isDarkMode ? '#FFFFFF' : '#000000'; // Putih untuk dark mode, hitam untuk light mode
    $gridColor = $isDarkMode ? 'rgba(200, 200, 200, 0.2)' : 'rgba(0, 0, 0, 0.1)';
    $legendTextColor = $isDarkMode ? '#FFFFFF' : '#374151';

    return [
        'maintainAspectRatio' => false,
        'height' => 350,
        'scales' => [
            'y' => [
                'beginAtZero' => true,
                'grid' => [
                    'color' => $gridColor,
                ],
            ],
            'x' => [
                'grid' => [
                    'color' => $gridColor,
                ],
            ]
        ],
        'plugins' => [
            'legend' => [
                'display' => true,
                'position' => 'bottom',
                'labels' => [
                    'color' => $legendTextColor,
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
        ],
        // Override warna garis untuk dataset tipe line
        'datasets' => [
            [
                'type' => 'line',
                'borderColor' => $lineColor,
                'backgroundColor' => 'transparent',
                'borderWidth' => 2,
                'tension' => 0.1,
                'fill' => false,
            ]
        ],
    ];
}


    protected function getData(): array
    {
        // Dapatkan 12 bulan terakhir
        $months = collect();
        $labels = [];
        $now = Carbon::now();
        
        for ($i = 11; $i >= 0; $i--) {
            $date = $now->copy()->subMonths($i);
            $months->push($date->format('Y-m'));
            $labels[] = $date->format('M Y'); // Label bulan singkat dan tahun (mis. Jan 2023)
        }
        
        // Dapatkan jumlah invoice untuk 12 bulan terakhir berdasarkan status
        $totalInvoices = [];
        $menungguInvoices = [];
        $lunasInvoices = [];
        $kadaluarsaInvoices = [];
        $selesaiInvoices = [];
        
        foreach ($months as $month) {
            $startOfMonth = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            $endOfMonth = Carbon::createFromFormat('Y-m', $month)->endOfMonth();
            
            // Total invoice per bulan
            $totalInvoices[] = DB::table('invoices')
                ->whereNull('deleted_at')
                ->whereDate('tgl_invoice', '>=', $startOfMonth)
                ->whereDate('tgl_invoice', '<=', $endOfMonth)
                ->count();
                
            // Invoice menunggu pembayaran per bulan
            $menungguInvoices[] = DB::table('invoices')
                ->whereNull('deleted_at')
                ->whereDate('tgl_invoice', '>=', $startOfMonth)
                ->whereDate('tgl_invoice', '<=', $endOfMonth)
                ->where('status_invoice', 'Menunggu Pembayaran')
                ->count();
                
            // Invoice lunas per bulan
            $lunasInvoices[] = DB::table('invoices')
                ->whereNull('deleted_at')
                ->whereDate('tgl_invoice', '>=', $startOfMonth)
                ->whereDate('tgl_invoice', '<=', $endOfMonth)
                ->where('status_invoice', 'Lunas')
                ->count();
                
            // Invoice kadaluarsa per bulan
            $kadaluarsaInvoices[] = DB::table('invoices')
                ->whereNull('deleted_at')
                ->whereDate('tgl_invoice', '>=', $startOfMonth)
                ->whereDate('tgl_invoice', '<=', $endOfMonth)
                ->where('status_invoice', 'Kadaluarsa')
                ->count();
                
            // Invoice selesai per bulan
            $selesaiInvoices[] = DB::table('invoices')
                ->whereNull('deleted_at')
                ->whereDate('tgl_invoice', '>=', $startOfMonth)
                ->whereDate('tgl_invoice', '<=', $endOfMonth)
                ->where('status_invoice', 'Selesai')
                ->count();
        }
        
        // Hitung total 
        $allTotal = array_sum($totalInvoices);
        $paidTotal = array_sum($lunasInvoices) + array_sum($selesaiInvoices);
        $paidPercentage = $allTotal > 0 ? round(($paidTotal / $allTotal) * 100) : 0;
        
        // Update heading dengan total
        self::$heading = "Jumlah Invoice Setiap Bulannya (Total: {$allTotal}, Terbayar: {$paidPercentage}%)";

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Total Invoice',
                    'data' => $totalInvoices,
                    'backgroundColor' => 'rgba(255, 255, 255, 0.5)',
                    'borderColor' => 'rgb(255, 255, 255)',
                    'borderWidth' => 1,
                    'type' => 'line',
                    'fill' => false,
                    'tension' => 0.1,
                ],
                [
                    'label' => 'Lunas',
                    'data' => $lunasInvoices,
                    'backgroundColor' => 'rgba(75, 192, 192, 0.7)', // Teal
                    'borderColor' => 'rgb(75, 192, 192)',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Selesai',
                    'data' => $selesaiInvoices,
                    'backgroundColor' => 'rgba(54, 162, 235, 0.7)', // Blue
                    'borderColor' => 'rgb(54, 162, 235)',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Menunggu Pembayaran',
                    'data' => $menungguInvoices,
                    'backgroundColor' => 'rgba(255, 159, 64, 0.7)', // Orange
                    'borderColor' => 'rgb(255, 159, 64)',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Kadaluarsa',
                    'data' => $kadaluarsaInvoices,
                    'backgroundColor' => 'rgba(255, 99, 132, 0.7)', // Red
                    'borderColor' => 'rgb(255, 99, 132)',
                    'borderWidth' => 1,
                ],
            ]
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
    
    protected function getFooter(): ?string
    {
        $statusCounts = $this->getStatusCounts();
        $totalInvoices = array_sum($statusCounts);
        
        if ($totalInvoices === 0) {
            return 'Belum ada invoice';
        }
        
        // Buat teks status dengan badge berwarna
        $statusHtml = '<div class="flex flex-wrap gap-2 justify-center">';
        
        $colors = [
            'Menunggu Pembayaran' => 'bg-orange-600',
            'Lunas' => 'bg-teal-600',
            'Kadaluarsa' => 'bg-red-600',
            'Selesai' => 'bg-blue-600',
            'Tidak Diketahui' => 'bg-gray-600'
        ];
        
        foreach ($statusCounts as $status => $count) {
            if ($count === 0) continue;
            
            $percentage = round(($count / $totalInvoices) * 100);
            $color = $colors[$status] ?? 'bg-gray-600';
            
            $statusHtml .= '
                <div class="' . $color . ' text-white text-xs px-2 py-1 rounded">
                    ' . $status . ': ' . $count . ' (' . $percentage . '%)
                </div>
            ';
        }
        
        $statusHtml .= '</div>';
        
        return $statusHtml;
    }
}