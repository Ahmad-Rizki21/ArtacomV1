<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LaporanInvoiceChart extends ChartWidget
{
    protected static ?string $heading = 'Status Invoice';

    protected function getData(): array
    {
        // Daftar status yang ingin ditampilkan
        $statuses = [
            'Menunggu Pembayaran',
            'Selesai',
            'Kadaluarsa',
            'Lunas'
        ];

        // Ambil data invoice per bulan untuk setiap status
        $invoiceData = Invoice::select(
            DB::raw('MONTH(created_at) as month'),
            'status_invoice',
            DB::raw('COUNT(*) as count')
        )
        ->whereIn('status_invoice', $statuses)
        ->whereYear('created_at', now()->year)
        ->groupBy('month', 'status_invoice')
        ->get();

        // Inisialisasi data untuk chart
        $monthLabels = [
            'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 
            'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
        ];

        $statusColors = [
            'Menunggu Pembayaran' => 'rgba(255, 99, 132, 0.6)',
            'Selesai' => 'rgba(54, 162, 235, 0.6)',
            'Kadaluarsa' => 'rgba(255, 206, 86, 0.6)',
            'Lunas' => 'rgba(75, 192, 192, 0.6)'
        ];

        // Siapkan dataset untuk setiap status
        $datasets = [];
        foreach ($statuses as $status) {
            $data = array_fill(0, 12, 0);
            
            $statusData = $invoiceData->where('status_invoice', $status);
            
            foreach ($statusData as $item) {
                $data[$item->month - 1] = $item->count;
            }

            $datasets[] = [
                'label' => $status,
                'data' => $data,
                'backgroundColor' => $statusColors[$status],
                'borderColor' => $statusColors[$status],
                'fill' => false
            ];
        }

        return [
            'datasets' => $datasets,
            'labels' => $monthLabels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}