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
     * Properti untuk menyimpan hasil kalkulasi agar bisa digunakan di footer.
     */
    protected array $statusCounts = [];

    protected function getData(): array
    {
        // 1. Siapkan label untuk 12 bulan terakhir
        $labels = [];
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $labels[] = $date->format('M Y');
            $months[] = $date->format('Y-m');
        }

        // 2. Jalankan HANYA SATU QUERY untuk mengambil semua data yang dibutuhkan
        $invoiceData = DB::table('invoices')
            ->select(
                DB::raw("DATE_FORMAT(tgl_invoice, '%Y-%m') as bulan"),
                'status_invoice',
                DB::raw('count(*) as jumlah')
            )
            ->where('tgl_invoice', '>=', now()->subMonths(11)->startOfMonth())
            ->whereNull('deleted_at')
            ->groupBy('bulan', 'status_invoice')
            ->get();

        // 3. Ubah hasil query menjadi format yang mudah diakses: ['2023-07']['Lunas'] => 15
        $keyedData = [];
        foreach ($invoiceData as $data) {
            $keyedData[$data->bulan][$data->status_invoice] = $data->jumlah;
        }

        // 4. Siapkan array dataset untuk chart
        $datasets = [
            'Total Invoice' => [],
            'Lunas' => [],
            'Selesai' => [],
            'Menunggu Pembayaran' => [],
            'Kadaluarsa' => [],
        ];

        // 5. Isi dataset dengan data dari query yang sudah diolah
        foreach ($months as $month) {
            $datasets['Lunas'][] = $keyedData[$month]['Lunas'] ?? 0;
            $datasets['Selesai'][] = $keyedData[$month]['Selesai'] ?? 0;
            $datasets['Menunggu Pembayaran'][] = $keyedData[$month]['Menunggu Pembayaran'] ?? 0;
            $datasets['Kadaluarsa'][] = $keyedData[$month]['Kadaluarsa'] ?? 0;

            // Hitung total per bulan
            $totalPerBulan = ($keyedData[$month]['Lunas'] ?? 0) + 
                             ($keyedData[$month]['Selesai'] ?? 0) + 
                             ($keyedData[$month]['Menunggu Pembayaran'] ?? 0) + 
                             ($keyedData[$month]['Kadaluarsa'] ?? 0);
            $datasets['Total Invoice'][] = $totalPerBulan;
        }

        // 6. Hitung total keseluruhan untuk ditampilkan di heading dan footer
        $allTotal = array_sum($datasets['Total Invoice']);
        $paidTotal = array_sum($datasets['Lunas']) + array_sum($datasets['Selesai']);
        $paidPercentage = $allTotal > 0 ? round(($paidTotal / $allTotal) * 100) : 0;
        
        // Simpan data untuk footer
        $this->statusCounts = [
            'Lunas' => array_sum($datasets['Lunas']),
            'Selesai' => array_sum($datasets['Selesai']),
            'Menunggu Pembayaran' => array_sum($datasets['Menunggu Pembayaran']),
            'Kadaluarsa' => array_sum($datasets['Kadaluarsa']),
            'Tidak Diketahui' => 0, // Asumsi tidak ada status ini dari query
        ];

        // Update heading chart secara dinamis
        self::$heading = "Jumlah Invoice Setiap Bulannya (Total: {$allTotal}, Terbayar: {$paidPercentage}%)";
        
        // 7. Kembalikan data dalam format yang dibutuhkan oleh ChartWidget
        $isDarkMode = config('filament.layout.theme') === 'dark';
        $lineColor = $isDarkMode ? '#FFFFFF' : '#000000';

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Total Invoice',
                    'data' => $datasets['Total Invoice'],
                    'type' => 'line',
                    'borderColor' => $lineColor,
                    'backgroundColor' => 'transparent',
                    'borderWidth' => 2,
                ],
                [
                    'label' => 'Lunas',
                    'data' => $datasets['Lunas'],
                    'backgroundColor' => 'rgba(75, 192, 192, 0.7)',
                ],
                [
                    'label' => 'Selesai',
                    'data' => $datasets['Selesai'],
                    'backgroundColor' => 'rgba(54, 162, 235, 0.7)',
                ],
                [
                    'label' => 'Menunggu Pembayaran',
                    'data' => $datasets['Menunggu Pembayaran'],
                    'backgroundColor' => 'rgba(255, 159, 64, 0.7)',
                ],
                [
                    'label' => 'Kadaluarsa',
                    'data' => $datasets['Kadaluarsa'],
                    'backgroundColor' => 'rgba(255, 99, 132, 0.7)',
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
