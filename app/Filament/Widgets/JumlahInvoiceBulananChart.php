<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class JumlahInvoiceBulananChart extends ChartWidget
{
    protected static ?string $heading = 'Jumlah Invoice Setiap Bulannya';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $pollingInterval = '60s';

    /**
     * Mendapatkan data untuk chart.
     *
     * @return array
     */
    protected function getData(): array
    {
        // Logika untuk mendeteksi mode gelap/terang
        $isDarkMode = config('filament.layout.theme') === 'dark';
        $lineColor = $isDarkMode ? '#FFFFFF' : '#000000'; // Putih untuk dark, Hitam untuk light

        // Menyiapkan label untuk 12 bulan terakhir
        $months = collect();
        $labels = [];
        $now = Carbon::now();

        for ($i = 11; $i >= 0; $i--) {
            $date = $now->copy()->subMonths($i);
            $months->push($date->format('Y-m'));
            $labels[] = $date->format('M Y'); // Format label: Jan 2023
        }

        // Mengambil data invoice dari database
        $totalInvoices = [];
        $menungguInvoices = [];
        $lunasInvoices = [];
        $kadaluarsaInvoices = [];
        $selesaiInvoices = [];

        foreach ($months as $month) {
            $startOfMonth = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            $endOfMonth = Carbon::createFromFormat('Y-m', $month)->endOfMonth();

            // Menggunakan whereBetween untuk query yang lebih efisien
            $totalInvoices[] = DB::table('invoices')->whereNull('deleted_at')->whereBetween('tgl_invoice', [$startOfMonth, $endOfMonth])->count();
            $menungguInvoices[] = DB::table('invoices')->whereNull('deleted_at')->whereBetween('tgl_invoice', [$startOfMonth, $endOfMonth])->where('status_invoice', 'Menunggu Pembayaran')->count();
            $lunasInvoices[] = DB::table('invoices')->whereNull('deleted_at')->whereBetween('tgl_invoice', [$startOfMonth, $endOfMonth])->where('status_invoice', 'Lunas')->count();
            $kadaluarsaInvoices[] = DB::table('invoices')->whereNull('deleted_at')->whereBetween('tgl_invoice', [$startOfMonth, $endOfMonth])->where('status_invoice', 'Kadaluarsa')->count();
            $selesaiInvoices[] = DB::table('invoices')->whereNull('deleted_at')->whereBetween('tgl_invoice', [$startOfMonth, $endOfMonth])->where('status_invoice', 'Selesai')->count();
        }

        // Menghitung total dan persentase untuk heading
        $allTotal = array_sum($totalInvoices);
        $paidTotal = array_sum($lunasInvoices) + array_sum($selesaiInvoices);
        $paidPercentage = $allTotal > 0 ? round(($paidTotal / $allTotal) * 100) : 0;

        // Update heading chart secara dinamis
        self::$heading = "Jumlah Invoice Setiap Bulannya (Total: {$allTotal}, Terbayar: {$paidPercentage}%)";

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Total Invoice',
                    'data' => $totalInvoices,
                    'type' => 'line', // Tipe chart line
                    'borderColor' => $lineColor, // Warna garis dinamis
                    'backgroundColor' => 'transparent', // Latar belakang transparan untuk line chart
                    'borderWidth' => 2,
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
            ],
        ];
    }

    /**
     * Mendapatkan opsi konfigurasi untuk chart.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        $isDarkMode = config('filament.layout.theme') === 'dark';

        // [PERBAIKAN] Gunakan variabel umum untuk semua warna teks (legenda dan sumbu)
        $textColor = $isDarkMode ? '#FFFFFF' : '#374151';
        $gridColor = $isDarkMode ? 'rgba(200, 200, 200, 0.2)' : 'rgba(0, 0, 0, 0.1)';

        return [
            'maintainAspectRatio' => false,
            'height' => 350,
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'color' => $gridColor,
                    ],
                    // [PERBAIKAN] Tambahkan warna untuk label (ticks) di sumbu Y
                    'ticks' => [
                        'color' => $textColor,
                    ],
                ],
                'x' => [
                    'grid' => [
                        'color' => $gridColor,
                    ],
                    // [PERBAIKAN] Tambahkan warna untuk label (ticks) di sumbu X
                    'ticks' => [
                        'color' => $textColor,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                    'labels' => [
                        // Terapkan warna teks yang sudah didefinisikan untuk legenda
                        'color' => $textColor,
                    ],
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) {
                            var label = context.dataset.label || "";
                            var value = context.parsed.y || 0;
                            return label + ": " + value;
                        }',
                    ],
                ],
            ],
        ];
    }

    /**
     * Mendapatkan jumlah invoice per status.
     *
     * @return array
     */
    protected function getStatusCounts(): array
    {
        $validStatuses = Invoice::VALID_STATUSES;

        $statusCounts = DB::table('invoices')
            ->select('status_invoice', DB::raw('COUNT(*) as total'))
            ->whereNull('deleted_at')
            ->groupBy('status_invoice')
            ->get()
            ->pluck('total', 'status_invoice')
            ->toArray();

        $filteredCounts = [];
        foreach ($validStatuses as $status) {
            $filteredCounts[$status] = $statusCounts[$status] ?? 0;
        }

        return $filteredCounts;
    }

    /**
     * Mendefinisikan tipe chart utama.
     *
     * @return string
     */
    protected function getType(): string
    {
        return 'bar'; // Tipe dasar adalah bar, namun dataset 'Total Invoice' di-override menjadi 'line'
    }

    /**
     * Membuat footer untuk widget chart.
     *
     * @return string|null
     */
    protected function getFooter(): ?string
    {
        $statusCounts = $this->getStatusCounts();
        $totalInvoices = array_sum($statusCounts);

        if ($totalInvoices === 0) {
            return 'Belum ada invoice';
        }

        // Membuat tampilan status dengan badge berwarna
        $statusHtml = '<div class="flex flex-wrap gap-2 justify-center">';

        $colors = [
            'Menunggu Pembayaran' => 'bg-orange-600',
            'Lunas' => 'bg-teal-600',
            'Kadaluarsa' => 'bg-red-600',
            'Selesai' => 'bg-blue-600',
            'Tidak Diketahui' => 'bg-gray-600',
        ];

        foreach ($statusCounts as $status => $count) {
            if ($count === 0) {
                continue;
            }

            $percentage = round(($count / $totalInvoices) * 100);
            $color = $colors[$status] ?? 'bg-gray-600';

            $statusHtml .= '
                <div class="' . $color . ' text-white text-xs px-2 py-1 rounded">
                    ' . htmlspecialchars($status) . ': ' . $count . ' (' . $percentage . '%)
                </div>
            ';
        }

        $statusHtml .= '</div>';

        return $statusHtml;
    }
}
