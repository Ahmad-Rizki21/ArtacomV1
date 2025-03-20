<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class LanggananTemplateExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize
{
    /**
     * @return array
     */
    public function array(): array
    {
        return [
            [
                '1', // pelanggan_id
                '1', // id_pelanggan
                '20Mbps-u', // profile_pppoe
                'Pinus Elok', // olt
                'Jakinet', // id_brand
                'Internet', // layanan
                '220890.00', // total_harga_layanan_x_pajak
                '2025-03-24', // tgl_jatuh_tempo
                'otomatis', // metode_pembayaran
                'aktif', // user_status
                '2025-02-24' // tgl_invoice_terakhir
            ],
            [
                '2', // pelanggan_id
                '2', // id_pelanggan
                '50Mbps-u', // profile_pppoe
                'Tambun', // olt
                'Jelantik', // id_brand
                'Internet', // layanan
                '350000.00', // total_harga_layanan_x_pajak
                '2025-04-15', // tgl_jatuh_tempo
                'manual', // metode_pembayaran
                'suspend', // user_status
                '2025-03-15' // tgl_invoice_terakhir
            ]
        ];
    }
    
    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'pelanggan_id',
            'id_pelanggan',
            'profile_pppoe',
            'olt',
            'id_brand',
            'layanan',
            'total_harga_layanan_x_pajak',
            'tgl_jatuh_tempo',
            'metode_pembayaran',
            'user_status',
            'tgl_invoice_terakhir'
        ];
    }
    
    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D3D3D3']]],
        ];
    }
}