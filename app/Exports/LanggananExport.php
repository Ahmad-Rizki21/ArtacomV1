<?php

namespace App\Exports;

use App\Models\Langganan;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithTitle;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class LanggananExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithEvents, WithTitle
{
    use Exportable;
    
    protected $brand;
    protected $status;
    
    /**
     * @param string|null $brand
     * @param string|null $status
     */
    public function __construct($brand = null, $status = null)
    {
        $this->brand = $brand;
        $this->status = $status;
    }
    
    /**
     * @return Builder
     */
    public function query()
    {
        $query = Langganan::query()->with('pelanggan');
        
        // Filter berdasarkan brand
        if ($this->brand && $this->brand !== 'all') {
            $query->where('id_brand', $this->brand);
        }
        
        // Filter berdasarkan status
        if ($this->status && $this->status !== 'all') {
            $query->where('user_status', $this->status);
        }
        
        return $query;
    }
    
    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'ID',
            'ID Pelanggan',
            'Nama Pelanggan',
            'Brand',
            'Layanan',
            'Profile PPPoE',
            'OLT',
            'Total Harga',
            'Tanggal Jatuh Tempo',
            'Metode Pembayaran',
            'Status User',
            'Tanggal Invoice Terakhir',
            'Tanggal Dibuat',
            'Tanggal Diupdate'
        ];
    }
    
    /**
     * @param mixed $row
     * @return array
     */
    public function map($row): array
    {
        // Fungsi untuk mengonversi tanggal
        $formatDate = function($date) {
            if (empty($date)) return null;
            
            // Jika sudah objek Carbon, gunakan format
            if ($date instanceof \Carbon\Carbon) {
                return $date->format('Y-m-d');
            }
            
            // Jika string, coba parsing
            try {
                return Carbon::parse($date)->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        };

        return [
            $row->id,
            $row->id_pelanggan,
            $row->pelanggan->nama ?? 'N/A',
            $row->id_brand,
            $row->layanan,
            $row->profile_pppoe,
            $row->olt,
            number_format($row->total_harga_layanan_x_pajak, 2, ',', '.'),
            $formatDate($row->tgl_jatuh_tempo),
            $row->metode_pembayaran,
            $row->user_status,
            $formatDate($row->tgl_invoice_terakhir),
            $formatDate($row->created_at),
            $formatDate($row->updated_at)
        ];
    }
    
    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text
            1 => ['font' => ['bold' => true]],
        ];
    }
    
    /**
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $event->sheet->getStyle('A1:N1')->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => [
                            'rgb' => 'D3D3D3',
                        ],
                    ],
                ]);
            },
        ];
    }
    
    /**
     * @return string
     */
    public function title(): string
    {
        $title = 'Semua Langganan';
        
        if ($this->brand && $this->brand !== 'all') {
            $title .= " - Brand {$this->brand}";
        }
        
        if ($this->status && $this->status !== 'all') {
            $title .= " - Status {$this->status}";
        }
        
        return $title;
    }
}