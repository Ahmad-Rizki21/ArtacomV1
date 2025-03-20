<?php

namespace App\Exports;

use App\Models\DataTeknis;
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

class DataTeknisExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithEvents, WithTitle
{
    use Exportable;
    
    protected $location;
    
    // Mapping lokasi untuk filter
    protected $locationMap = [
        'all' => null,
        'Nagrak' => ['Rusun Nagrak'],
        'Pinus Elok' => ['Rusun Pinus Elok'],
        'Pulogebang Tower' => ['Rusun Pulogebang Tower'],
        'Tipar Cakung' => ['Rusun Tipar Cakung'],
        'Tambun' => ['Rusun Tambun'],
        'Parama' => ['Perumahan Parama Serang'],
        'Waringin' => ['Perumahan Waringin Kurung']
    ];
    
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
        $query = DataTeknis::query()->with('pelanggan');
        
        // Filter berdasarkan lokasi
        if ($this->location && $this->location !== 'all') {
            // Jika lokasi ada di mapping
            if (isset($this->locationMap[$this->location])) {
                $query->whereHas('pelanggan', function($q) {
                    $matchLocations = $this->locationMap[$this->location];
                    $q->where(function($subQuery) use ($matchLocations) {
                        foreach ($matchLocations as $location) {
                            $subQuery->orWhere('alamat', 'like', '%' . $location . '%')
                                     ->orWhere('alamat_2', 'like', '%' . $location . '%');
                        }
                    });
                });
            }
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
            'Alamat Pelanggan',
            'ID VLAN',
            'IP Pelanggan',
            'Password PPPoE',
            'Profile PPPoE',
            'OLT',
            'OLT Custom',
            'PON',
            'OTB',
            'ODC',
            'ODP',
            'ONU Power',
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
            $row->pelanggan_id,
            $row->pelanggan->nama ?? 'N/A',
            $row->pelanggan->alamat ?? 'N/A',
            $row->id_vlan,
            $row->ip_pelanggan,
            $row->password_pppoe,
            $row->profile_pppoe,
            $row->olt,
            $row->olt_custom,
            $row->pon,
            $row->otb,
            $row->odc,
            $row->odp,
            $row->onu_power,
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
        return $this->location && $this->location !== 'all' 
            ? "Data Teknis - {$this->location}" 
            : 'Semua Data Teknis';
    }
}