<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Events\InvoiceCreated;
use Illuminate\Support\Facades\Log;
use App\Services\XenditService;
use Carbon\Carbon;


class Invoice extends Model
{
    use HasFactory;

    protected $table = 'invoices';

    protected $fillable = [
        'invoice_number',
        'pelanggan_id',
        'id_pelanggan',
        'brand',
        'total_harga',
        'no_telp',
        'email',
        'tgl_invoice',
        'tgl_jatuh_tempo',
        'payment_link',
        'status_invoice',
        'xendit_id',
        'xendit_external_id',
        'paid_amount',
        'paid_at'
    ];

    protected $casts = [
        'total_harga' => 'decimal:2',
        'tgl_invoice' => 'date',
        'tgl_jatuh_tempo' => 'date',
        'paid_amount' => 'decimal:2',
        'paid_at' => 'datetime'
    ];

    // Daftar status yang valid
    public const VALID_STATUSES = [
        'Menunggu Pembayaran',
        'Lunas',
        'Kadaluarsa',
        'Selesai',
        'Tidak Diketahui'
    ];


    public static function rules() {
        return [
            'invoice_number' => 'required|string',
            'pelanggan_id' => 'required|integer',
            'id_pelanggan' => 'required|string',
            'brand' => 'required|string',
            'total_harga' => 'required|numeric',
            'no_telp' => 'required|string',
            'email' => 'required|email',
            'tgl_invoice' => 'required|date',
            'tgl_jatuh_tempo' => 'required|date',
            'status_invoice' => 'required|string'
        ];
    }
    


    /**
     * Update status invoice dari Xendit
     */


     public function updateXenditStatus(string $xenditStatus, ?string $xenditId = null, ?array $additionalData = null)
{
    $statusMapping = [
        'PENDING' => 'Menunggu Pembayaran',
        'PAID' => 'Lunas',
        'SETTLED' => 'Selesai',
        'EXPIRED' => 'Kadaluarsa'
    ];

    // Mapping status dari Xendit ke status internal
    $newStatus = $statusMapping[strtoupper($xenditStatus)] ?? 'Tidak Diketahui';

    $updateData = [
        'status_invoice' => $newStatus
    ];

    // Jika ada Xendit ID dan data pembayaran tambahan
    if ($xenditId) {
        $updateData['xendit_id'] = $xenditId;
    }

    if ($additionalData) {
        if (isset($additionalData['paid_amount'])) {
            $updateData['paid_amount'] = number_format($additionalData['paid_amount'], 2, '.', '');
        }

        if (isset($additionalData['paid_at'])) {
            $updateData['paid_at'] = Carbon::parse($additionalData['paid_at']);
        }
    }

    // Update status invoice
    $this->fill($updateData);
    $this->save();

    Log::info('Invoice status updated in database', [
        'invoice_number' => $this->invoice_number,
        'status' => $this->status_invoice
    ]);

    return $this;
}

public function updateStatusFromWebhook(string $status, float $paidAmount = 0, ?string $paidAt = null)
{
    // Pemetaan status dari Xendit ke status internal
    $statusMapping = [
        'PENDING' => 'Menunggu Pembayaran',
        'SETTLED' => 'Selesai',
        'EXPIRED' => 'Kadaluarsa',
        'PAID' => 'Lunas',
    ];

    // Tentukan status baru berdasarkan pemetaan
    $newStatus = $statusMapping[strtoupper($status)] ?? 'Tidak Diketahui';

    // Perbarui status invoice
    $this->status_invoice = $newStatus;

    // Perbarui jumlah yang dibayar jika ada
    if ($paidAmount > 0) {
        $this->paid_amount = $paidAmount;
    }

    // Perbarui waktu pembayaran jika ada
    if ($paidAt) {
        $this->paid_at = Carbon::parse($paidAt);
    }

    // Simpan pembaruan status
    $this->save();

    // Log untuk memverifikasi perubahan
    Log::info('Invoice status updated from webhook', [
        'invoice_number' => $this->invoice_number,
        'new_status' => $this->status_invoice
    ]);
}




//     public function updateXenditStatus(string $status, ?string $xenditId = null): self
// {
//     // Pemetaan status dari Xendit
//     $statusMapping = [
//         'PENDING' => 'Menunggu Pembayaran',
//         'PAID' => 'Lunas',
//         'SETTLED' => 'Selesai',
//         'EXPIRED' => 'Kadaluarsa',
//     ];

//     // Konversi status dengan aman
//     $newStatus = $statusMapping[strtoupper($status)] ?? 'Tidak Diketahui';

//     // Log detail update
//     Log::info('Updating Invoice Status', [
//         'invoice_number' => $this->invoice_number,
//         'current_status' => $this->status_invoice,
//         'new_status' => $newStatus,
//         'xendit_status' => $status
//     ]);

//     // Update status dan Xendit ID
//     $this->status_invoice = $newStatus;
//     if ($xenditId) {
//         $this->xendit_id = $xenditId;
//     }

//     // Simpan perubahan
//     $this->save();

//     return $this;
// }


//     /**
//      * Update dari callback webhook Xendit
//      */
//     public function updateFromXenditCallback(
//         string $xenditStatus, 
//         ?string $xenditId = null, 
//         ?string $externalId = null,
//         ?float $paidAmount = null,
//         ?string $paidAt = null
//     ): self {
//         // Pemetaan status
//         $statusMapping = [
//             'PENDING' => 'Menunggu Pembayaran',
//             'PAID' => 'Lunas',
//             'SETTLED' => 'Selesai',
//             'EXPIRED' => 'Kadaluarsa'
//         ];

//         // Konversi status
//         $newStatus = $statusMapping[strtoupper($xenditStatus)] ?? 'Tidak Diketahui';

//         // Siapkan data update
//         $updateData = [
//             'status_invoice' => $newStatus
//         ];

//         // Tambahkan Xendit ID jika disediakan
//         if ($xenditId) {
//             $updateData['xendit_id'] = $xenditId;
//         }

//         // Tambahkan External ID jika disediakan
//         if ($externalId) {
//             $updateData['xendit_external_id'] = $externalId;
//         }

//         // Tambahkan informasi pembayaran
//         if ($paidAmount !== null) {
//             $updateData['paid_amount'] = $paidAmount;
//         }

//         if ($paidAt) {
//             $updateData['paid_at'] = $paidAt;
//         }

//         // Update invoice
//         $this->update($updateData);

//         // Logging
//         Log::info('Invoice Updated from Xendit Callback', [
//             'invoice_number' => $this->invoice_number,
//             'old_status' => $this->getOriginal('status_invoice'),
//             'new_status' => $newStatus,
//             'xendit_status' => $xenditStatus
//         ]);

//         return $this;
//     }




        // public function updateXenditStatus(string $xenditStatus, ?string $xenditId = null, ?array $additionalData = null)
        // {
        //     Log::info('🔍 Memulai update status invoice', [
        //         'invoice_number' => $this->invoice_number,
        //         'status' => $xenditStatus,
        //         'additional_data' => $additionalData
        //     ]);

        //     $statusMapping = [
        //         'PENDING' => 'Menunggu Pembayaran',
        //         'PAID' => 'Lunas',
        //         'SETTLED' => 'Selesai',
        //         'EXPIRED' => 'Kadaluarsa'
        //     ];

        //     $newStatus = $statusMapping[strtoupper($xenditStatus)] ?? 'Tidak Diketahui';

        //     $updateData = ['status_invoice' => $newStatus];

        //     if ($xenditId) {
        //         $updateData['xendit_id'] = $xenditId;
        //     }

        //     if ($additionalData) {
        //         if (isset($additionalData['paid_amount'])) {
        //             $updateData['paid_amount'] = number_format($additionalData['paid_amount'], 2, '.', '');
        //         }

        //         if (isset($additionalData['paid_at'])) {
        //             $updateData['paid_at'] = \Carbon\Carbon::parse($additionalData['paid_at']);
        //         }
        //     }

            

        //     $this->fill($updateData);
        //     $this->save();

        //     Log::info('✅ Status Invoice Berhasil Diperbarui', [
        //         'invoice_number' => $this->invoice_number,
        //         'new_status' => $newStatus,
        //         'paid_amount' => $updateData['paid_amount'] ?? null,
        //         'paid_at' => $updateData['paid_at'] ?? null
        //     ]);

        //     return $this;
        // }

        
        
        

public function updateStatusFromXendit()
{
    // Pastikan xendit_id tersedia
    if (!$this->xendit_id) {
        Log::warning('Tidak dapat memeriksa status - Xendit ID kosong', [
            'invoice_number' => $this->invoice_number
        ]);
        return false;
    }

    // Dapatkan nama brand dari model HargaLayanan
    $brand = HargaLayanan::where('id_brand', $this->brand)->value('brand');

    // Gunakan service untuk memeriksa status
    $xenditService = new XenditService();
    $invoiceStatus = $xenditService->checkInvoiceStatus($this->xendit_id, $brand);

    if (!$invoiceStatus) {
        Log::error('Gagal mendapatkan status invoice', [
            'invoice_number' => $this->invoice_number,
            'xendit_id' => $this->xendit_id
        ]);
        return false;
    }

    // Mapping status
    $statusMapping = [
        'PENDING' => 'Menunggu Pembayaran',
        'PAID' => 'Lunas',
        'SETTLED' => 'Selesai',
        'EXPIRED' => 'Kadaluarsa'
    ];

    // Update status
    $newStatus = $statusMapping[$invoiceStatus['status']] ?? 'Tidak Diketahui';

    // Siapkan data update
    $updateData = [
        'status_invoice' => $newStatus
    ];

    // Tambahkan informasi pembayaran dengan pengecekan
    if (isset($invoiceStatus['amount'])) {
        $updateData['paid_amount'] = $invoiceStatus['amount'];
    }

    if (isset($invoiceStatus['paid_at'])) {
        $updateData['paid_at'] = $invoiceStatus['paid_at'];
    }

    // Update invoice
    $this->update($updateData);

    Log::info('Invoice status updated from Xendit', [
        'invoice_number' => $this->invoice_number,
        'old_status' => $this->getOriginal('status_invoice'),
        'new_status' => $newStatus
    ]);

    return true;
}



    /**
     * Metode untuk mencari invoice berdasarkan Xendit ID
     */
    public static function findByXenditId(string $xenditId): ?self
    {
        return self::where('xendit_id', $xenditId)->first();
    }

    /**
     * Metode untuk mencari invoice berdasarkan Xendit External ID
     */
    public static function findByXenditExternalId(string $externalId): ?self
    {
        return self::where('invoice_number', $externalId)
            ->orWhere('xendit_external_id', $externalId)
            ->first();
    }

    /**
     * Ekstrak Xendit ID dari payment link
     */
    public function extractXenditIdFromPaymentLink(): ?string
    {
        if (empty($this->payment_link)) {
            return null;
        }
        
        // Format payment link: https://checkout-staging.xendit.co/web/67bd6f886049810aae8e7379
        $parts = explode('/', $this->payment_link);
        
        // Pastikan Xendit ID ditemukan dan valid
        $xenditId = end($parts); 
        return $xenditId && strlen($xenditId) > 0 ? $xenditId : null;
    }

    /**
     * Method boot untuk inisialisasi saat membuat invoice
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($invoice) {
            Log::info('Creating Invoice: ', $invoice->toArray());
    
            // Generate Nomor Invoice secara otomatis
            $invoice->invoice_number = 'INV-' . now()->format('Ymd') . '-' . rand(1000, 9999);
    
            // Ambil ID Pelanggan dan data lainnya...
            $dataTeknis = DataTeknis::where('pelanggan_id', $invoice->pelanggan_id)->first();
            if ($dataTeknis) {
                $invoice->id_pelanggan = $dataTeknis->id_pelanggan;
            } else {
                throw new \Exception('ID Pelanggan tidak ditemukan di Data Teknis.');
            }
    
            // Ambil `brand` dari `langganan`
            $langganan = Langganan::where('pelanggan_id', $invoice->pelanggan_id)->first();
            if ($langganan) {
                $invoice->brand = $langganan->id_brand;
                $invoice->total_harga = $langganan->total_harga_layanan_x_pajak;
            } else {
                throw new \Exception('Brand tidak ditemukan untuk pelanggan ini.');
            }
    
            // Ambil `no_telp` dan `email` dari `pelanggan`
            $pelanggan = Pelanggan::find($invoice->pelanggan_id);
            if ($pelanggan) {
                $invoice->no_telp = $pelanggan->no_telp;
                $invoice->email = $pelanggan->email;
            } else {
                throw new \Exception('Nomor Telepon atau Email tidak ditemukan untuk pelanggan ini.');
            }
    
            // Set status awal
            $invoice->status_invoice = 'Menunggu Pembayaran';

                // Tambahkan logika untuk menetapkan tanggal jatuh tempo
            if (!$invoice->tgl_jatuh_tempo) {
                // Jika tidak ada tanggal jatuh tempo, set 1 bulan dari tanggal invoice
                $invoice->tgl_jatuh_tempo = Carbon::parse($invoice->tgl_invoice)->addMonth();
            }
        });
    
        static::created(function ($invoice) {
            Log::info('Invoice Created: ', $invoice->toArray());
            event(new InvoiceCreated($invoice));
        });


        
    }

    // Relasi ke Pelanggan
    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class, 'pelanggan_id', 'id');
    }

    // Relasi ke Data Teknis (ID Pelanggan dari ISP)
    public function dataTeknis()
    {
        return $this->belongsTo(DataTeknis::class, 'id_pelanggan', 'id_pelanggan');
    }

    // Relasi ke Langganan
    public function langganan()
    {
        return $this->hasOne(Langganan::class, 'pelanggan_id', 'pelanggan_id');
    }

    // Relasi ke Harga Layanan
    public function hargaLayanan()
    {
        return $this->hasOne(HargaLayanan::class, 'id_brand', 'brand');
    }
}
