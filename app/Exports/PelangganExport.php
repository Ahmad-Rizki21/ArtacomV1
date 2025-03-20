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

class PelangganExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithEvents, WithTitle
{
    use Exportable;
    
    protected $location;
    
    /**
     * @param string|null $location
     */
    public function __construct($location = null)
    {
        $this->location = $location;
    }
    
    /**
     * @return Builder
     */
    public function query()
    {
        $query = Pelanggan::query();
        
        if ($this->location) {
            $query->where(function($query) {
                $query->where('alamat', 'like', '%' . $this->location . '%')
                      ->orWhere('alamat_2', 'like', '%' . $this->location . '%');
            });
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
            'Blok',
            'Unit',
            'No Telepon',
            'Email',
            'Alamat Tambahan',
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
        return [
            $row->id,
            $row->no_ktp,
            $row->nama,
            $row->alamat,
            $row->blok,
            $row->unit,
            $row->no_telp,
            $row->email,
            $row->alamat_2,
            $row->created_at ? $row->created_at->format('Y-m-d H:i:s') : null,
            $row->updated_at ? $row->updated_at->format('Y-m-d H:i:s') : null,
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
                $event->sheet->getStyle('A1:K1')->applyFromArray([
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
        return $this->location ? "Pelanggan - {$this->location}" : 'Semua Pelanggan';
    }
}