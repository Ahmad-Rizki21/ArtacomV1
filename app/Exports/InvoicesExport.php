<?php

namespace App\Exports;

use App\Models\Invoice;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithTitle;

class InvoicesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithEvents, WithTitle
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Invoice::with(['pelanggan', 'hargaLayanan'])->get();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'ID',
            'Nomor Invoice',
            'Nama Pelanggan',  // Ubah dari Pelanggan ID
            'ID Pelanggan',
            'Brand',           // Tetap, tetapi akan ditampilkan nama brand, bukan kode
            'Total Harga',
            'Nomor Telepon',
            'Email',
            'Tanggal Invoice',
            'Tanggal Jatuh Tempo',
            'Link Pembayaran',
            'Status Invoice',
            'Sedang Diproses',
            'Xendit ID',
            'Tanggal Dibuat',
            'Tanggal Diupdate',
            'Xendit External ID',
            'Jumlah Dibayar',
            'Tanggal Pembayaran',
            'Tanggal Dihapus'
        ];
    }

    /**
     * @param mixed $invoice
     * @return array
     */
    public function map($invoice): array
    {
        // Fungsi untuk mendapatkan nama brand
        $getBrandName = function($brandCode) {
            return match ($brandCode) {
                'ajn-01' => 'Jakinet',
                'ajn-02' => 'Jelantik',
                'ajn-03' => 'Jelantik Nagrak',
                default => $brandCode,
            };
        };

        return [
            $invoice->id,
            $invoice->invoice_number,
            $invoice->pelanggan->nama ?? 'N/A',  // Tampilkan nama pelanggan, bukan ID
            $invoice->id_pelanggan,
            $getBrandName($invoice->brand),      // Tampilkan nama brand, bukan kode
            $invoice->total_harga,
            $invoice->no_telp,
            $invoice->email,
            $invoice->tgl_invoice ? $invoice->tgl_invoice->format('Y-m-d') : null,
            $invoice->tgl_jatuh_tempo ? $invoice->tgl_jatuh_tempo->format('Y-m-d') : null,
            $invoice->payment_link,
            $invoice->status_invoice,
            $invoice->is_processing ? 'Ya' : 'Tidak',
            $invoice->xendit_id,
            $invoice->created_at ? $invoice->created_at->format('Y-m-d H:i:s') : null,
            $invoice->updated_at ? $invoice->updated_at->format('Y-m-d H:i:s') : null,
            $invoice->xendit_external_id,
            $invoice->paid_amount,
            $invoice->paid_at ? $invoice->paid_at->format('Y-m-d H:i:s') : null,
            $invoice->deleted_at ? $invoice->deleted_at->format('Y-m-d H:i:s') : null
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
                $event->sheet->getStyle('A1:T1')->applyFromArray([
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
        return 'Daftar Invoice';
    }
}