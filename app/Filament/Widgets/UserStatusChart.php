<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Langganan;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UserStatusChart extends ChartWidget
{
    protected static ?string $heading = 'Status User';

    protected function getData(): array
    {
        // Dapatkan bulan-bulan dalam setahun
        $monthLabels = [
            'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 
            'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
        ];

        // Inisialisasi data untuk pengguna aktif, suspend, dan belum ada invoice
        $activeUsers = array_fill(0, 12, 0);
        $suspendedUsers = array_fill(0, 12, 0);
        $noInvoiceUsers = array_fill(0, 12, 0);

        // Dapatkan semua langganan
        $subscriptions = Langganan::whereYear('created_at', now()->year)
            ->get();
            
        // Grup langganan berdasarkan bulan
        $subscriptionsByMonth = $subscriptions->groupBy(function($item) {
            return Carbon::parse($item->created_at)->format('n'); // 'n' returns month without leading zeros
        });
        
        // Untuk setiap bulan, hitung jumlah pengguna berdasarkan status
        foreach ($subscriptionsByMonth as $month => $monthSubscriptions) {
            // Inisialisasi counter untuk bulan ini
            $activeCount = 0;
            $suspendCount = 0;
            $noInvoiceCount = 0;
            
            // Periksa status setiap langganan
            foreach ($monthSubscriptions as $subscription) {
                $status = $subscription->user_status;
                
                if ($status === 'Aktif') {
                    $activeCount++;
                } elseif ($status === 'Suspend') {
                    $suspendCount++;
                } else {
                    $noInvoiceCount++;
                }
            }
            
            // Simpan jumlah ke dalam array data
            // Ingat bulan dimulai dari 1, tapi array kita dimulai dari 0
            $monthIndex = (int)$month - 1;
            $activeUsers[$monthIndex] = $activeCount;
            $suspendedUsers[$monthIndex] = $suspendCount;
            $noInvoiceUsers[$monthIndex] = $noInvoiceCount;
        }

        return [
            'datasets' => [
                [
                    'label' => 'User Aktif',
                    'data' => $activeUsers,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.6)', // Hijau
                    'borderColor' => 'rgba(34, 197, 94, 1)',
                    'fill' => false
                ],
                [
                    'label' => 'User Suspend',
                    'data' => $suspendedUsers,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.6)', // Merah
                    'borderColor' => 'rgba(239, 68, 68, 1)',
                    'fill' => false
                ],
                [
                    'label' => 'Belum Ada Invoice',
                    'data' => $noInvoiceUsers,
                    'backgroundColor' => 'rgba(156, 163, 175, 0.6)', // Abu-abu
                    'borderColor' => 'rgba(156, 163, 175, 1)',
                    'fill' => false
                ]
            ],
            'labels' => $monthLabels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}