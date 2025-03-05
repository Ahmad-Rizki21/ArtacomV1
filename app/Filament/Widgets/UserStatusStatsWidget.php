<?php

namespace App\Filament\Widgets;

use App\Models\Langganan;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UserStatusStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1; // Tempatkan di atas widget lainnya
    
    protected function getStats(): array
    {
        // Dapatkan data langsung dari model Langganan, sama seperti di tabel
        $allSubscriptions = Langganan::all();
        $totalCustomers = $allSubscriptions->count();
        
        // Hitung jumlah pelanggan berdasarkan status
        $activeCustomers = 0;
        $suspendedCustomers = 0;
        $noInvoiceCustomers = 0;
        
        // Gunakan metode getUserStatusAttribute yang sama dengan yang ditampilkan di tabel
        foreach ($allSubscriptions as $subscription) {
            $status = $subscription->user_status;
            
            if ($status === 'Aktif') {
                $activeCustomers++;
            } elseif ($status === 'Suspend') {
                $suspendedCustomers++;
            } else {
                $noInvoiceCustomers++;
            }
        }
        
        // Hitung persentase
        $activePercentage = $totalCustomers > 0 ? round(($activeCustomers / $totalCustomers) * 100) : 0;
        $suspendedPercentage = $totalCustomers > 0 ? round(($suspendedCustomers / $totalCustomers) * 100) : 0;
        $noInvoicePercentage = $totalCustomers > 0 ? round(($noInvoiceCustomers / $totalCustomers) * 100) : 0;

        return [
            Stat::make('Total Pelanggan', $totalCustomers)
                ->description('Seluruh pelanggan berlangganan')
                ->color('gray')
                ->chart([
                    $activePercentage, 
                    $suspendedPercentage, 
                    $noInvoicePercentage
                ]),
                
            Stat::make('User Aktif', $activeCustomers)
                ->description($activePercentage . '% dari total pelanggan')
                ->color('success')
                ->chart([
                    $activePercentage,
                    $activePercentage,
                    $activePercentage
                ]),
                
            Stat::make('User Suspend', $suspendedCustomers)
                ->description($suspendedPercentage . '% dari total pelanggan')
                ->color('danger')
                ->chart([
                    $suspendedPercentage,
                    $suspendedPercentage,
                    $suspendedPercentage
                ]),
                
            Stat::make('Belum Ada Invoice', $noInvoiceCustomers)
                ->description($noInvoicePercentage . '% dari total pelanggan')
                ->color('gray')
                ->chart([
                    $noInvoicePercentage,
                    $noInvoicePercentage,
                    $noInvoicePercentage
                ]),
        ];
    }
    public static function getSort(): int
    {
        return -2; // Membuat widget ini memiliki prioritas tinggi
    }
}