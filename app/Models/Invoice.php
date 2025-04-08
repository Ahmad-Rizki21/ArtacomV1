<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Events\InvoiceCreated;
use Illuminate\Support\Facades\Log;
use App\Services\XenditService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

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
        'paid_at',
        'tgl_bayar'
    ];

    // Pastikan created_at dan updated_at dikelola dengan benar
    protected $dates = [
        'deleted_at', 
        'tgl_invoice', 
        'tgl_jatuh_tempo', 
        'paid_at',
        'tgl_bayar', // Tambahkan field baru
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'total_harga' => 'decimal:2',
        'tgl_invoice' => 'date',
        'tgl_jatuh_tempo' => 'date',
        'paid_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
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

    public function getPaidAtJakartaAttribute()
{
    return optional($this->paid_at)->setTimezone('Asia/Jakarta');
}


    /**
     * Update status invoice dari Xendit
     * Method ini juga akan mengupdate status langganan dan tanggal jatuh tempo
     */
    public function updateXenditStatus(string $xenditStatus, ?string $xenditId = null, ?array $additionalData = null)
    {
        Log::info('Memulai update status invoice dari Xendit', [
            'invoice_number' => $this->invoice_number,
            'xendit_status' => $xenditStatus,
            'xendit_id' => $xenditId
        ]);

        $statusMapping = [
            'PENDING' => 'Menunggu Pembayaran',
            'PAID' => 'Lunas',
            'SETTLED' => 'Selesai',
            'EXPIRED' => 'Kadaluarsa'
        ];

        $newStatus = $statusMapping[strtoupper($xenditStatus)] ?? 'Tidak Diketahui';

        $updateData = [
            'status_invoice' => $newStatus
        ];

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

        Log::info('Status invoice diperbarui', [
            'invoice_number' => $this->invoice_number,
            'new_status' => $newStatus
        ]);

       // Jika status invoice adalah 'Lunas' atau 'Selesai'
                    if (in_array($newStatus, ['Lunas', 'Selesai'])) {
                        // Ambil langganan terkait
                        $langganan = $this->langganan;
                        
                        if ($langganan) {
                            // Periksa metode pembayaran dan ubah jika perlu
                            if ($langganan->metode_pembayaran === 'manual') {
                                // Ubah metode pembayaran ke otomatis
                                $langganan->metode_pembayaran = 'otomatis';
                                
                                // Hitung ulang total harga berdasarkan tabel harga
                                $hargaLayanan = HargaLayanan::find($langganan->id_brand);
                                if ($hargaLayanan) {
                                    $harga = match ($langganan->layanan) {
                                        '10 Mbps' => $hargaLayanan->harga_10mbps,
                                        '20 Mbps' => $hargaLayanan->harga_20mbps,
                                        '30 Mbps' => $hargaLayanan->harga_30mbps,
                                        '50 Mbps' => $hargaLayanan->harga_50mbps,
                                        default => 0,
                                    };
                                    
                                    // Set total harga sesuai dengan nilai yang sudah disepakati
                                    if ($hargaLayanan->id_brand === 'ajn-01') { // Jakinet
                                        if ($langganan->layanan === '10 Mbps') $total = 150000;
                                        else if ($langganan->layanan === '20 Mbps') $total = 220890;
                                        else if ($langganan->layanan === '30 Mbps') $total = 248640;
                                        else if ($langganan->layanan === '50 Mbps') $total = 281940;
                                    } else if ($hargaLayanan->id_brand === 'ajn-02') { // Jelantik
                                        if ($langganan->layanan === '10 Mbps') $total = 166500;
                                        else if ($langganan->layanan === '20 Mbps') $total = 231990;
                                        else if ($langganan->layanan === '30 Mbps') $total = 276390;
                                        else if ($langganan->layanan === '50 Mbps') $total = 321789;
                                    } else if ($hargaLayanan->id_brand === 'ajn-03') { // Jalinet (Nagrak)
                                        if ($langganan->layanan === '10 Mbps') $total = 150000;
                                        else if ($langganan->layanan === '20 Mbps') $total = 220890;
                                        else if ($langganan->layanan === '30 Mbps') $total = 248640;
                                        else if ($langganan->layanan === '50 Mbps') $total = 281940;
                                    } else {
                                        // Untuk brand lain, gunakan formula standar
                                        $pajak = floor(($hargaLayanan->pajak / 100) * $harga);
                                        $total = $harga + $pajak;
                                        $total = ceil($total / 1000) * 1000; // Bulatkan ke kelipatan 1000
                                    }
                                    
                                    $langganan->total_harga_layanan_x_pajak = $total;
                                }
                                
                                // Simpan perubahan
                                $langganan->save();
                                
                                Log::info('Metode pembayaran diubah dari manual ke otomatis melalui Xendit', [
                                    'invoice_number' => $this->invoice_number,
                                    'pelanggan_id' => $this->pelanggan_id,
                                    'total_harga_baru' => $langganan->total_harga_layanan_x_pajak
                                ]);
                            }
                            
                            // Update tanggal jatuh tempo dan status langganan
                            // Format tanggal invoice ke format yang benar sebelum mengirimkannya
                            $tglInvoice = $this->tgl_invoice ? Carbon::parse($this->tgl_invoice)->format('Y-m-d') : null;
                            
                            Log::info('Mengirim data invoice untuk update langganan', [
                                'invoice_number' => $this->invoice_number,
                                'tanggal_invoice' => $tglInvoice
                            ]);
                            
                            $updated = $langganan->updateTanggalJatuhTempo($tglInvoice, $this->invoice_number);
                            
                            Log::info('Hasil update langganan setelah pembayaran', [
                                'invoice_number' => $this->invoice_number,
                                'pelanggan_id' => $this->pelanggan_id,
                                'tanggal_invoice' => $this->tgl_invoice,
                                'updated' => $updated,
                                'metode_pembayaran' => $langganan->metode_pembayaran
                            ]);
                        } else {
                            Log::warning('Langganan tidak ditemukan untuk invoice ini', [
                                'invoice_number' => $this->invoice_number,
                                'pelanggan_id' => $this->pelanggan_id
                            ]);
                        }
                    }

                    return $this;
    }

    /**
     * Update status dari webhook
     */
    public function updateStatusFromWebhook(string $status, float $paidAmount = 0, ?string $paidAt = null)
    {
        Log::info('Menerima update status dari webhook', [
            'invoice_number' => $this->invoice_number,
            'status' => $status,
            'paid_amount' => $paidAmount,
            'paid_at' => $paidAt
        ]);

        // Pemetaan status dari Xendit ke status internal
        $statusMapping = [
            'PENDING' => 'Menunggu Pembayaran',
            'SETTLED' => 'Selesai',
            'EXPIRED' => 'Kadaluarsa',
            'PAID' => 'Lunas',
        ];

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

        Log::info('Status invoice diperbarui dari webhook', [
            'invoice_number' => $this->invoice_number,
            'new_status' => $this->status_invoice
        ]);

        // Jika status invoice adalah 'Lunas' atau 'Selesai'
        if (in_array($newStatus, ['Lunas', 'Selesai'])) {
            // Ambil langganan terkait
            $langganan = $this->langganan;
            
            if ($langganan) {
                // Format tanggal invoice ke format yang benar sebelum mengirimkannya
                $tglInvoice = $this->tgl_invoice ? Carbon::parse($this->tgl_invoice)->format('Y-m-d') : null;
                
                Log::info('Memperbarui langganan dari webhook', [
                    'invoice_number' => $this->invoice_number,
                    'tanggal_invoice' => $tglInvoice
                ]);
                
                // Update tanggal jatuh tempo dan status langganan
                $langganan->updateTanggalJatuhTempo($tglInvoice);
                
                Log::info('Langganan diperbarui setelah pembayaran webhook', [
                    'invoice_number' => $this->invoice_number,
                    'pelanggan_id' => $this->pelanggan_id,
                    'tgl_jatuh_tempo' => $langganan->tgl_jatuh_tempo,
                    'status' => $langganan->user_status
                ]);
            } else {
                Log::warning('Langganan tidak ditemukan untuk invoice ini', [
                    'invoice_number' => $this->invoice_number,
                    'pelanggan_id' => $this->pelanggan_id
                ]);
            }
        }

        return true;
    }

    /**
     * Update status invoice dari Xendit API
     */
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
            Log::error('Gagal mendapatkan status invoice dari Xendit API', [
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

        // Konversi status
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

        Log::info('Status invoice diperbarui dari Xendit API', [
            'invoice_number' => $this->invoice_number,
            'old_status' => $this->getOriginal('status_invoice'),
            'new_status' => $newStatus
        ]);

        // Jika status invoice adalah 'Lunas' atau 'Selesai'
        if (in_array($newStatus, ['Lunas', 'Selesai'])) {
            // Update status dan tanggal jatuh tempo langganan
            $langganan = $this->langganan;
            
            if ($langganan) {
                $langganan->updateTanggalJatuhTempo($this->tgl_invoice);
            }
        }

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
    
            // Ambil data pelanggan dan set tanggal jatuh tempo
            if ($invoice->pelanggan_id) {
                // Ambil langganan untuk mendapatkan tanggal jatuh tempo
                $langganan = Langganan::where('pelanggan_id', $invoice->pelanggan_id)->first();
                if ($langganan && $langganan->tgl_jatuh_tempo) {
                    $invoice->tgl_jatuh_tempo = $langganan->tgl_jatuh_tempo;
                } else {
                    // Jika tidak ada tanggal jatuh tempo di langganan, gunakan tanggal invoice
                    $invoice->tgl_jatuh_tempo = $invoice->tgl_invoice ?? now();
                }
    
                // Ambil ID Pelanggan dari Data Teknis
                $dataTeknis = DataTeknis::where('pelanggan_id', $invoice->pelanggan_id)->first();
                if ($dataTeknis) {
                    $invoice->id_pelanggan = $dataTeknis->id_pelanggan;
                }
    
                // Ambil data pelanggan
                $pelanggan = Pelanggan::find($invoice->pelanggan_id);
                if ($pelanggan) {
                    $invoice->no_telp = $pelanggan->no_telp;
                    $invoice->email = $pelanggan->email;
                }
    
                // Ambil brand dan total harga dari langganan
                if ($langganan) {
                    $invoice->brand = $langganan->id_brand;
                    $invoice->total_harga = $langganan->total_harga_layanan_x_pajak;
                }
            }
        

            // Tambahkan logika untuk menetapkan tanggal jatuh tempo
            // if (!$invoice->tgl_jatuh_tempo) {
            //     // Jika tidak ada tanggal jatuh tempo, set 1 bulan dari tanggal invoice
            //     $invoice->tgl_jatuh_tempo = Carbon::parse($invoice->tgl_invoice)->addMonth();
            // }
            if (!$invoice->tgl_jatuh_tempo) {
                // Jika tidak ada tanggal jatuh tempo, set pada hari yang sama pukul 23:59:59
                $invoice->tgl_jatuh_tempo = Carbon::parse($invoice->tgl_invoice)->endOfDay();
            }


        });
    
        static::created(function ($invoice) {
            Log::info('Invoice Created: ', $invoice->toArray());
            event(new InvoiceCreated($invoice));
        });
        
        // Tambahkan listener untuk updated event
                static::updated(function ($invoice) {
                    Log::info('Invoice Updated: ', [
                        'invoice_number' => $invoice->invoice_number,
                        'status' => $invoice->status_invoice
                    ]);
                    
                    // Jika status invoice berubah menjadi 'Lunas' atau 'Selesai'
                    if (in_array($invoice->status_invoice, ['Lunas', 'Selesai']) && 
                        !in_array($invoice->getOriginal('status_invoice'), ['Lunas', 'Selesai'])) {
                        
                        // Update status dan tanggal jatuh tempo langganan
                        $langganan = $invoice->langganan;
                        
                        if ($langganan) {
                            // Periksa metode pembayaran dan ubah jika manual
                            if ($langganan->metode_pembayaran === 'manual') {
                                // Ubah metode pembayaran ke otomatis
                                $langganan->metode_pembayaran = 'otomatis';
                                
                                // Hitung ulang total harga berdasarkan tabel harga
                                $hargaLayanan = HargaLayanan::find($langganan->id_brand);
                                if ($hargaLayanan) {
                                    $harga = match ($langganan->layanan) {
                                        '10 Mbps' => $hargaLayanan->harga_10mbps,
                                        '20 Mbps' => $hargaLayanan->harga_20mbps,
                                        '30 Mbps' => $hargaLayanan->harga_30mbps,
                                        '50 Mbps' => $hargaLayanan->harga_50mbps,
                                        default => 0,
                                    };
                                    
                                    // Set total harga sesuai dengan nilai yang sudah disepakati
                                    if ($hargaLayanan->id_brand === 'ajn-01') { // Jakinet
                                        if ($langganan->layanan === '10 Mbps') $total = 150000;
                                        else if ($langganan->layanan === '20 Mbps') $total = 220890;
                                        else if ($langganan->layanan === '30 Mbps') $total = 248640;
                                        else if ($langganan->layanan === '50 Mbps') $total = 281940;
                                    } else if ($hargaLayanan->id_brand === 'ajn-02') { // Jelantik
                                        if ($langganan->layanan === '10 Mbps') $total = 166500;
                                        else if ($langganan->layanan === '20 Mbps') $total = 231990;
                                        else if ($langganan->layanan === '30 Mbps') $total = 276390;
                                        else if ($langganan->layanan === '50 Mbps') $total = 321789;
                                    } else if ($hargaLayanan->id_brand === 'ajn-03') { // Jalinet (Nagrak)
                                        if ($langganan->layanan === '10 Mbps') $total = 150000;
                                        else if ($langganan->layanan === '20 Mbps') $total = 220890;
                                        else if ($langganan->layanan === '30 Mbps') $total = 248640;
                                        else if ($langganan->layanan === '50 Mbps') $total = 281940;
                                    } else {
                                        // Untuk brand lain, gunakan formula standar
                                        $pajak = floor(($hargaLayanan->pajak / 100) * $harga);
                                        $total = $harga + $pajak;
                                        $total = ceil($total / 1000) * 1000; // Bulatkan ke kelipatan 1000
                                    }
                                    
                                    $langganan->total_harga_layanan_x_pajak = $total;
                                }
                                
                                // Simpan perubahan
                                $langganan->save();
                                
                                Log::info('Metode pembayaran diubah dari manual ke otomatis melalui event updated', [
                                    'invoice_number' => $invoice->invoice_number,
                                    'pelanggan_id' => $invoice->pelanggan_id,
                                    'total_harga_baru' => $langganan->total_harga_layanan_x_pajak
                                ]);
                            }
                            
                            // Update tanggal jatuh tempo
                            $formattedDate = $invoice->tgl_invoice ? Carbon::parse($invoice->tgl_invoice)->format('Y-m-d') : null;
                            $langganan->updateTanggalJatuhTempo($formattedDate, $invoice->invoice_number);
                            
                            Log::info('Langganan diperbarui setelah invoice lunas', [
                                'invoice_number' => $invoice->invoice_number,
                                'pelanggan_id' => $invoice->pelanggan_id,
                                'status_langganan' => $langganan->user_status,
                                'tgl_jatuh_tempo' => $langganan->tgl_jatuh_tempo,
                                'metode_pembayaran' => $langganan->metode_pembayaran
                            ]);
                        }
                    }
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
    
                /**
             * Method untuk menangani pembayaran sukses
             * Ini akan mengupdate status langganan dan tanggal jatuh tempo
             */
            public function handleSuccessfulPayment()
            {
                // Update status invoice menjadi Lunas jika belum
                if ($this->status_invoice !== 'Lunas' && $this->status_invoice !== 'Selesai') {
                    $this->status_invoice = 'Lunas';
                    $this->save();
                }
                
                // Update langganan
                $langganan = $this->langganan;
                
                if ($langganan) {
                    // Format tanggal invoice ke format yang benar
                    $tglInvoice = $this->tgl_invoice ? Carbon::parse($this->tgl_invoice)->format('Y-m-d') : null;
                    
                    Log::info('Menangani pembayaran sukses', [
                        'invoice_number' => $this->invoice_number,
                        'tanggal_invoice' => $tglInvoice
                    ]);
                    
                    // Cek apakah langganan menggunakan metode manual (prorate)
                    if ($langganan->metode_pembayaran === 'manual') {
                        // Ubah metode pembayaran ke otomatis
                        $langganan->metode_pembayaran = 'otomatis';
                        
                        // Hitung ulang total harga berdasarkan tabel harga
                        $hargaLayanan = HargaLayanan::find($langganan->id_brand);
                        if ($hargaLayanan) {
                            $harga = match ($langganan->layanan) {
                                '10 Mbps' => $hargaLayanan->harga_10mbps,
                                '20 Mbps' => $hargaLayanan->harga_20mbps,
                                '30 Mbps' => $hargaLayanan->harga_30mbps,
                                '50 Mbps' => $hargaLayanan->harga_50mbps,
                                default => 0,
                            };
                            
                            // Set total harga sesuai dengan nilai yang sudah disepakati
                            if ($hargaLayanan->id_brand === 'ajn-01') { // Jakinet
                                if ($langganan->layanan === '10 Mbps') $total = 150000;
                                else if ($langganan->layanan === '20 Mbps') $total = 220890;
                                else if ($langganan->layanan === '30 Mbps') $total = 248640;
                                else if ($langganan->layanan === '50 Mbps') $total = 281940;
                            } else if ($hargaLayanan->id_brand === 'ajn-02') { // Jelantik
                                if ($langganan->layanan === '10 Mbps') $total = 166500;
                                else if ($langganan->layanan === '20 Mbps') $total = 231990;
                                else if ($langganan->layanan === '30 Mbps') $total = 276390;
                                else if ($langganan->layanan === '50 Mbps') $total = 321789;
                            } else if ($hargaLayanan->id_brand === 'ajn-03') { // Jalinet (Nagrak)
                                if ($langganan->layanan === '10 Mbps') $total = 150000;
                                else if ($langganan->layanan === '20 Mbps') $total = 220890;
                                else if ($langganan->layanan === '30 Mbps') $total = 248640;
                                else if ($langganan->layanan === '50 Mbps') $total = 281940;
                            } else {
                                // Untuk brand lain, gunakan formula standar
                                $pajak = floor(($hargaLayanan->pajak / 100) * $harga);
                                $total = $harga + $pajak;
                                $total = ceil($total / 1000) * 1000; // Bulatkan ke kelipatan 1000
                            }
                            
                            $langganan->total_harga_layanan_x_pajak = $total;
                        }
                        
                        // Simpan perubahan
                        $langganan->save();
                        
                        Log::info('Metode pembayaran diubah dari manual ke otomatis setelah pembayaran', [
                            'invoice_number' => $this->invoice_number,
                            'pelanggan_id' => $this->pelanggan_id,
                            'total_harga_baru' => $langganan->total_harga_layanan_x_pajak
                        ]);
                    }
                    
                    return $langganan->updateTanggalJatuhTempo($tglInvoice, $this->invoice_number);
                }
                
                return false;
            }
}