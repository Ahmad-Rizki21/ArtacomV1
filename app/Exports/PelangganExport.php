<?php

namespace App\Exports;

use App\Models\Pelanggan;
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

class PelangganExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithEvents, WithTitle
{
    use Exportable;
    
    protected $location;
    protected $brand;
    
    /**
     * @param string|null $location
     * @param string|null $brand
     */
    public function __construct($location = null, $brand = null)
    {
        $this->location = $location;
        $this->brand = $brand;
    }
    
    /**
     * @return Builder
     */
    public function query()
    {
        $query = Pelanggan::query();
        
        // Filter berdasarkan lokasi
        if ($this->location) {
            $query->where(function($query) {
                $query->where('alamat', 'like', '%' . $this->location . '%')
                      ->orWhere('alamat_2', 'like', '%' . $this->location . '%');
            });
        }
        
        // Filter berdasarkan brand
        if ($this->brand) {
            $query->where('id_brand', $this->brand);
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
            'No KTP',
            'Nama',
            'Alamat',
            'Alamat Tambahan',
            'Tanggal Instalasi',
            'Blok',
            'Unit',
            'No Telepon',
            'Email',
            'Brand',
            'Nama Brand', // Tambahkan kolom untuk nama brand
            'Layanan',
            'Brand Default',
            'Alamat Tambahan 2',
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
        // Fungsi helper untuk format tanggal dengan aman
        $formatDate = function($date) {
            if (empty($date)) {
                return null;
            }
            
            // Jika sudah objek Carbon
            if ($date instanceof Carbon) {
                return $date->format('Y-m-d');
            }
            
            // Coba parse string ke Carbon
            try {
                return Carbon::parse($date)->format('Y-m-d');
            } catch (\Exception $e) {
                return $date; // Return as is if cannot parse
            }
        };
        
        // Fungsi helper untuk format timestamp
        $formatTimestamp = function($date) {
            if (empty($date)) {
                return null;
            }
            
            // Jika sudah objek Carbon
            if ($date instanceof Carbon) {
                return $date->format('Y-m-d H:i:s');
            }
            
            // Coba parse string ke Carbon
            try {
                return Carbon::parse($date)->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                return $date; // Return as is if cannot parse
            }
        };

       // Mapping ID brand ke nama brand
        $brandMap = [
            'ajn-01' => 'Jakinet',
            'ajn-02' => 'Jelantik',
            'ajn-03' => 'Jelantik Nagrak',
            // Tambahkan mapping brand lainnya jika ada
        ];

        // Konversi ID brand ke nama brand
        $brandName = isset($brandMap[$row->id_brand]) ? $brandMap[$row->id_brand] : $row->id_brand;

        return [
            $row->id,
            $row->no_ktp,
            $row->nama,
            $row->alamat,
            $row->alamat_2,
            $formatDate($row->tgl_instalasi),
            $row->blok,
            $row->unit,
            $row->no_telp,
            $row->email,
            $row->id_brand, // Tetap menampilkan ID brand
            $brandName,     // Menambahkan nama brand
            $row->layanan,
            $row->brand_default,
            $row->alamat_2,
            $formatTimestamp($row->created_at),
            $formatTimestamp($row->updated_at),
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
                $event->sheet->getStyle('A1:Q1')->applyFromArray([
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
        $title = 'Semua Pelanggan';
        
        if ($this->location) {
            $title = "Pelanggan - {$this->location}";
        }
        
        if ($this->brand) {
            $title .= $this->location ? " - Brand {$this->brand}" : "Pelanggan - Brand {$this->brand}";
        }
        
        return $title;
    }
}