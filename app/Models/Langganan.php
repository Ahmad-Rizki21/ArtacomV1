<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Services\MikrotikSubscriptionManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

class Langganan extends Model
{
    use HasFactory;

    protected $table = 'langganan';

    protected $fillable = [
        'pelanggan_id',
        'id_brand',
        'layanan',
        'total_harga_layanan_x_pajak',
        'tgl_jatuh_tempo',
        'tgl_invoice_terakhir',
        'metode_pembayaran',
        'user_status',  
        'id_pelanggan',
        'profile_pppoe',
        'olt',
        'last_processed_invoice',
    ];

    protected $dates = [
        'tgl_jatuh_tempo',
        'tgl_invoice_terakhir',
        'created_at',
        'updated_at'
    ];

    // Relasi ke pelanggan
    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class, 'pelanggan_id');
    }

    // Relasi ke harga layanan
    public function hargaLayanan()
    {
        return $this->belongsTo(HargaLayanan::class, 'id_brand', 'id_brand');
    }

    // Relasi ke invoice
    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'pelanggan_id', 'pelanggan_id');
    }

    // Relasi ke data teknis
    public function dataTeknis()
    {
        return $this->belongsTo(DataTeknis::class, 'pelanggan_id', 'pelanggan_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($langganan) {
            // Ambil data teknis menggunakan pelanggan_id
            $dataTeknis = DataTeknis::where('pelanggan_id', $langganan->pelanggan_id)->first();
        
            if ($dataTeknis) {
                // Salin data teknis ke dalam langganan
                $langganan->profile_pppoe = $dataTeknis->profile_pppoe;
                $langganan->id_pelanggan = $dataTeknis->id_pelanggan;
                $langganan->olt = $dataTeknis->olt;
            }
            
            // Ambil informasi layanan berdasarkan id_brand jika tersedia
            if ($langganan->id_brand) {
                $hargaLayanan = HargaLayanan::find($langganan->id_brand);
                if ($hargaLayanan) {
                    // Tentukan layanan berdasarkan profile_pppoe
                    if ($langganan->profile_pppoe) {
                        // Ekstrak kecepatan dari profile_pppoe (misalnya "10Mbps-a" -> "10 Mbps")
                        $matches = [];
                        if (preg_match('/(\d+)Mbps/', $langganan->profile_pppoe, $matches)) {
                            $langganan->layanan = $matches[1] . ' Mbps';
                        }
                    }
                    
                    // Hitung total harga setelah semua data tersedia
                    $langganan->hitungTotalHarga();
                }
            }
            
            // Atur tanggal jika belum diatur
            if (is_null($langganan->tgl_jatuh_tempo)) {
                $langganan->setTanggalJatuhTempo();
            }
        
            // Set status awal langganan ke Suspend
            $langganan->user_status = 'Suspend';
        });

        // Tambahkan observer untuk pemantauan perubahan
        static::updating(function ($langganan) {
            // Cek jika tanggal jatuh tempo diubah
            if ($langganan->isDirty('tgl_jatuh_tempo')) {
                // Menambahkan log untuk tracking perubahan
                Log::info('Tanggal jatuh tempo diubah', [
                    'pelanggan_id' => $langganan->pelanggan_id,
                    'tanggal_lama' => $langganan->getOriginal('tgl_jatuh_tempo'),
                    'tanggal_baru' => $langganan->tgl_jatuh_tempo
                ]);
                
                $langganan->cekStatusJatuhTempo();
            }
        });
    }

//     public function hitungTotalHarga($isManual = false, $manualHarga = null)
// {
//     if ($isManual && $manualHarga !== null) {
//         $this->total_harga_layanan_x_pajak = ceil($manualHarga / 1000) * 1000;
//         return $this->total_harga_layanan_x_pajak;
//     }

//     if (!$this->id_brand) {
//         return 0;
//     }

//     $hargaLayanan = HargaLayanan::find($this->id_brand);
//     if ($hargaLayanan) {
//         // Jika layanan belum ditentukan, coba ekstrak dari profile_pppoe
//         if (!$this->layanan && $this->profile_pppoe) {
//             $matches = [];
//             if (preg_match('/(\d+)Mbps/', $this->profile_pppoe, $matches)) {
//                 $this->layanan = $matches[1] . ' Mbps';
//             }
//         }

//         // Penanganan khusus untuk Jelantik Nagrak (ajn-03)
//         if ($hargaLayanan->id_brand === 'ajn-03') {
//             // Gunakan harga dari Jakinet (ajn-01)
//             $jakinetHarga = HargaLayanan::where('id_brand', 'ajn-01')->first();
            
//             if ($jakinetHarga) {
//                 $harga = match ($this->layanan) {
//                     '10 Mbps' => $jakinetHarga->harga_10mbps,
//                     '20 Mbps' => $jakinetHarga->harga_20mbps,
//                     '30 Mbps' => $jakinetHarga->harga_30mbps,
//                     '50 Mbps' => $jakinetHarga->harga_50mbps,
//                     default => 0,
//                 };
                
//                 $pajak = ($hargaLayanan->pajak / 100) * $harga;
//                 $total = $harga + $pajak;
//                 $totalBulat = ceil($total / 1000) * 1000;
                
//                 $this->total_harga_layanan_x_pajak = $totalBulat;
//                 return $totalBulat;
//             }
//         }

//         // Perhitungan normal untuk brand lain
//         $harga = match ($this->layanan) {
//             '10 Mbps' => $hargaLayanan->harga_10mbps,
//             '20 Mbps' => $hargaLayanan->harga_20mbps,
//             '30 Mbps' => $hargaLayanan->harga_30mbps,
//             '50 Mbps' => $hargaLayanan->harga_50mbps,
//             default => 0,
//         };

//         $pajak = ($hargaLayanan->pajak / 100) * $harga;
//         $total = $harga + $pajak;
//         $totalBulat = ceil($total / 1000) * 1000;
        
//         $this->total_harga_layanan_x_pajak = $totalBulat;
//         return $totalBulat;
//     }

//     return 0;
// }



public function hitungTotalHarga($isManual = false, $manualHarga = null)
{
    // Jika metode pembayaran manual dan ada harga yang diinput, gunakan itu
    if ($this->metode_pembayaran == 'manual' && $this->total_harga_layanan_x_pajak && !$isManual) {
        // Jika sudah ada nilai total dari input manual, gunakan itu
        return $this->total_harga_layanan_x_pajak;
    }

    // Jika eksplisit memanggil dengan harga manual
    if ($isManual && $manualHarga !== null) {
        $this->total_harga_layanan_x_pajak = $manualHarga;
        return $this->total_harga_layanan_x_pajak;
    }

    if (!$this->id_brand) {
        return 0;
    }

    $hargaLayanan = HargaLayanan::find($this->id_brand);
    if ($hargaLayanan) {
        // Jika layanan belum ditentukan, coba ekstrak dari profile_pppoe
        if (!$this->layanan && $this->profile_pppoe) {
            $matches = [];
            if (preg_match('/(\d+)Mbps/', $this->profile_pppoe, $matches)) {
                $this->layanan = $matches[1] . ' Mbps';
            }
        }

        // Mendapatkan harga dasar
        $harga = match ($this->layanan) {
            '10 Mbps' => $hargaLayanan->harga_10mbps,
            '20 Mbps' => $hargaLayanan->harga_20mbps,
            '30 Mbps' => $hargaLayanan->harga_30mbps,
            '50 Mbps' => $hargaLayanan->harga_50mbps,
            default => 0,
        };

        // Menghitung pajak dengan floor untuk menghindari angka berkoma
        $pajak = floor(($hargaLayanan->pajak / 100) * $harga);
        
        // Hitung total
        $total = $harga + $pajak;
        
        // Bulatkan ke atas ke kelipatan 1000
        $totalBulat = ceil($total / 1000) * 1000;
        
        // Untuk harga Jakinet, bulatkan ke nilai khusus
        if ($hargaLayanan->id_brand === 'ajn-01') {
            if ($this->layanan === '10 Mbps') $totalBulat = 150000;
            else if ($this->layanan === '20 Mbps') $totalBulat = 220890;
            else if ($this->layanan === '30 Mbps') $totalBulat = 248640; 
            else if ($this->layanan === '50 Mbps') $totalBulat = 281940;
        }
        
        // Untuk harga Jelantik, bulatkan ke nilai khusus
        if ($hargaLayanan->id_brand === 'ajn-02') {
            if ($this->layanan === '10 Mbps') $totalBulat = 166500;
            else if ($this->layanan === '20 Mbps') $totalBulat = 231990;
            else if ($this->layanan === '30 Mbps') $totalBulat = 276390;
            else if ($this->layanan === '50 Mbps') $totalBulat = 321789;
        }

        // Untuk harga Jelantik Nagrak, bulatkan ke nilai khusus
        if ($hargaLayanan->id_brand === 'ajn-03') {
            if ($this->layanan === '10 Mbps') $totalBulat = 150000;
            else if ($this->layanan === '20 Mbps') $totalBulat = 220890;
            else if ($this->layanan === '30 Mbps') $totalBulat = 248640; 
            else if ($this->layanan === '50 Mbps') $totalBulat = 281940;
        }

        $this->total_harga_layanan_x_pajak = $totalBulat;
        return $totalBulat;
    }

    return 0;
}




    public function setTanggalJatuhTempo($tanggalBerlangganan = null)
    {
        $tanggal = $tanggalBerlangganan ? Carbon::parse($tanggalBerlangganan) : Carbon::now();
        
        if (!$this->tgl_jatuh_tempo) {
            $this->tgl_jatuh_tempo = $tanggal->copy()->addMonth()->startOfMonth();
        }

        return $this;
    }

    public function cekStatusJatuhTempo()
    {
        // Pastikan tanggal jatuh tempo tidak kosong
        if (!$this->tgl_jatuh_tempo) {
            return true;
        }

        // Konversi tanggal jatuh tempo ke Carbon
        $tanggalJatuhTempo = Carbon::parse($this->tgl_jatuh_tempo);
        
        // Cek apakah tanggal jatuh tempo sudah lewat
        if ($tanggalJatuhTempo->isPast()) {
            // Simpan status lama
            $oldStatus = $this->user_status;
            
            // Jika status saat ini Aktif, ubah menjadi Suspend
            if ($oldStatus === 'Aktif') {
                $this->user_status = 'Suspend';
                $this->save();

                // Log perubahan status
                Log::info('Status langganan diubah menjadi Suspend karena tanggal jatuh tempo sudah lewat', [
                    'pelanggan_id' => $this->pelanggan_id,
                    'tgl_jatuh_tempo' => $this->tgl_jatuh_tempo,
                    'old_status' => $oldStatus
                ]);

                // Update status di Mikrotik
                try {
                    $mikrotikManager = app(MikrotikSubscriptionManager::class);
                    $mikrotikManager->handleSubscriptionStatus($this, 'suspend');

                    // Kirim notifikasi Filament
                    \Filament\Notifications\Notification::make()
                        ->title('Layanan Disuspend')
                        ->body("Layanan internet Anda ({$this->pelanggan->nama}) telah disuspend karena melewati tanggal jatuh tempo.")
                        ->warning()
                        ->sendToDatabase($this->pelanggan);
                } catch (\Exception $e) {
                    Log::error('Gagal menonaktifkan user di Mikrotik', [
                        'pelanggan_id' => $this->pelanggan_id,
                        'error' => $e->getMessage()
                    ]);
                }

                return false;
            }
        }

        return true;
    }

    /**
     * Update tanggal jatuh tempo setelah pembayaran
     * Method ini dipanggil dari Invoice setelah pembayaran berhasil
     * @param string|null $invoiceDate Tanggal invoice yang dibayar
     * @param string|null $invoiceNumber Nomor invoice yang dibayar
     * @return bool
     */
    public function updateTanggalJatuhTempo($invoiceDate = null, $invoiceNumber = null)
    {
        try {
            // Log informasi untuk debugging
            Log::info('Memperbarui tanggal jatuh tempo langganan', [
                'pelanggan_id' => $this->pelanggan_id,
                'tanggal_lama' => $this->tgl_jatuh_tempo,
                'tanggal_invoice' => $invoiceDate,
                'invoice_number' => $invoiceNumber
            ]);

            // Cek jika invoice sudah diproses sebelumnya (dengan nomor invoice)
            if (!empty($invoiceNumber) && !empty($this->last_processed_invoice) && $this->last_processed_invoice === $invoiceNumber) {
                Log::info('Invoice sudah diproses sebelumnya, melewati update', [
                    'pelanggan_id' => $this->pelanggan_id,
                    'invoice_number' => $invoiceNumber
                ]);
                return false;
            }

            // Jika tidak ada tanggal invoice, gunakan tanggal hari ini
            if (empty($invoiceDate)) {
                $invoiceDate = now()->format('Y-m-d');
                Log::info('Tanggal invoice kosong, menggunakan tanggal hari ini', [
                    'pelanggan_id' => $this->pelanggan_id,
                    'tanggal_hari_ini' => $invoiceDate
                ]);
            }

            // Konversi tanggal invoice ke objek Carbon
            $tanggalInvoiceCarbon = Carbon::parse($invoiceDate);

            // Format tanggal yang benar
            if (is_string($invoiceDate)) {
                $invoiceDate = Carbon::parse($invoiceDate)->format('Y-m-d');
            }

            // Simpan status lama sebelum diubah
            $oldStatus = $this->user_status;
            
            // Buat tanggal jatuh tempo 1 bulan dari tanggal berlangganan (bukan tanggal invoice)
            $tanggalBerlangganan = $this->tgl_jatuh_tempo ? Carbon::parse($this->tgl_jatuh_tempo) : $tanggalInvoiceCarbon;
            $tanggalJatuhTempo = $tanggalBerlangganan->copy()->addMonthNoOverflow();
            
            // Update tanggal jatuh tempo
            $this->tgl_jatuh_tempo = $tanggalJatuhTempo;
            
            // Update tanggal invoice terakhir jika ada
            if (!empty($invoiceDate)) {
                Log::info('Mengupdate tanggal invoice terakhir', [
                    'pelanggan_id' => $this->pelanggan_id,
                    'tanggal_invoice' => $invoiceDate
                ]);
                $this->tgl_invoice_terakhir = $invoiceDate;
            } else {
                Log::warning('Tanggal invoice kosong, tidak dapat mengupdate tgl_invoice_terakhir', [
                    'pelanggan_id' => $this->pelanggan_id
                ]);
            }
            
            // Catat invoice yang sudah diproses
            if (!empty($invoiceNumber)) {
                $this->last_processed_invoice = $invoiceNumber;
            }
            
            // Update juga status pengguna menjadi Aktif
            $this->user_status = 'Aktif';
            
            // Simpan perubahan dalam transaction untuk menghindari race condition
            DB::transaction(function() {
                $this->save();
            });
            
            // Log hasil update
            Log::info('Tanggal jatuh tempo berhasil diperbarui', [
                'pelanggan_id' => $this->pelanggan_id,
                'tanggal_baru' => $this->tgl_jatuh_tempo,
                'status_baru' => $this->user_status
            ]);
            
            // Update status di Mikrotik jika status berubah dari Suspend ke Aktif
            if ($oldStatus === 'Suspend' && $this->user_status === 'Aktif') {
                try {
                    $mikrotikManager = app(\App\Services\MikrotikSubscriptionManager::class);
                    $mikrotikManager->handleSubscriptionStatus($this, 'activate');
                } catch (\Exception $e) {
                    Log::error('Gagal mengaktifkan user di Mikrotik', [
                        'pelanggan_id' => $this->pelanggan_id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('Gagal mengupdate tanggal jatuh tempo', [
                'pelanggan_id' => $this->pelanggan_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Update status langganan berdasarkan status invoice terakhir
     */
    public function updateStatus()
    {
        // Ambil invoice terakhir untuk pelanggan ini
        $latestInvoice = $this->invoices()->latest('created_at')->first();

        if ($latestInvoice) {
            // Simpan status lama sebelum diubah
            $oldStatus = $this->user_status;
            
            // Jika status invoice adalah Lunas atau Selesai, update status langganan menjadi Aktif
            if (in_array($latestInvoice->status_invoice, ['Lunas', 'Selesai'])) {
                $this->user_status = 'Aktif';
                $this->save();
                
                Log::info('Status langganan diperbarui menjadi Aktif', [
                    'pelanggan_id' => $this->pelanggan_id,
                    'invoice_number' => $latestInvoice->invoice_number
                ]);
                
                // Update status di Mikrotik jika status berubah
                if ($oldStatus !== 'Aktif') {
                    try {
                        $mikrotikManager = app(\App\Services\MikrotikSubscriptionManager::class);
                        $mikrotikManager->handleSubscriptionStatus($this, 'activate');
                    } catch (\Exception $e) {
                        Log::error('Gagal mengaktifkan user di Mikrotik', [
                            'pelanggan_id' => $this->pelanggan_id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
                
                return true;
            } else {
                // Jika tidak, status Suspend
                $this->user_status = 'Suspend';
                $this->save();
                
                Log::info('Status langganan diperbarui menjadi Suspend', [
                    'pelanggan_id' => $this->pelanggan_id,
                    'invoice_number' => $latestInvoice->invoice_number,
                    'invoice_status' => $latestInvoice->status_invoice
                ]);
                
                // Update status di Mikrotik jika status berubah
                if ($oldStatus !== 'Suspend') {
                    try {
                        $mikrotikManager = app(\App\Services\MikrotikSubscriptionManager::class);
                        $mikrotikManager->handleSubscriptionStatus($this, 'suspend');
                    } catch (\Exception $e) {
                        Log::error('Gagal menonaktifkan user di Mikrotik', [
                            'pelanggan_id' => $this->pelanggan_id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
                
                return true;
            }
        }
        
        return false;
    }



    /**
 * Ubah metode pembayaran ke otomatis setelah bayar prorate
 */
public function switchToAutomaticPayment()
{
    // Ubah metode pembayaran ke otomatis
    $this->metode_pembayaran = 'otomatis';
    
    // Hitung ulang total harga berdasarkan paket dan brand
    $this->hitungTotalHarga();
    
    // Simpan perubahan
    $this->save();
    
    Log::info('Metode pembayaran diubah ke otomatis setelah prorate dibayar', [
        'pelanggan_id' => $this->pelanggan_id,
        'invoice_number' => $this->last_processed_invoice,
        'total_harga_baru' => $this->total_harga_layanan_x_pajak
    ]);
    
    return true;
}




    /**
     * Fungsi untuk menangani pembayaran invoice
     * Setelah invoice dibayar, update status langganan dan tanggal jatuh tempo
     */
    public function handlePayment($invoiceId)
    {
        $invoice = Invoice::find($invoiceId);
        
        if (!$invoice) {
            Log::warning('Invoice tidak ditemukan', ['invoice_id' => $invoiceId]);
            return false;
        }
        
        if (in_array($invoice->status_invoice, ['Lunas', 'Selesai'])) {
            // Cek apakah invoice sudah diproses
            if (!empty($this->last_processed_invoice) && $this->last_processed_invoice === $invoice->invoice_number) {
                Log::info('Invoice sudah diproses, melewati update', [
                    'invoice_id' => $invoiceId,
                    'invoice_number' => $invoice->invoice_number
                ]);
                return false;
            }
            
            try {
                // Update dalam transaction untuk menghindari race condition
                DB::transaction(function() use ($invoice) {
                    // Cek apakah ini prorate payment
                    $isProrate = $this->metode_pembayaran === 'manual';
                    
                    // Update status langganan menjadi Aktif
                    $this->user_status = 'Aktif';
                    
                    // Update tanggal jatuh tempo ke bulan berikutnya
                    if ($this->tgl_jatuh_tempo) {
                        $this->tgl_jatuh_tempo = Carbon::parse($this->tgl_jatuh_tempo)->addMonth();
                    } else {
                        // Jika tidak ada tanggal jatuh tempo, gunakan tanggal invoice + 1 bulan
                        $tanggalInvoice = $invoice->tgl_invoice ?? now();
                        $this->tgl_jatuh_tempo = Carbon::parse($tanggalInvoice)->addMonth();
                    }
                    
                    // Update tanggal invoice terakhir
                    if ($invoice->tgl_invoice) {
                        $this->tgl_invoice_terakhir = $invoice->tgl_invoice;
                    }
                    
                    // Catat invoice yang sudah diproses
                    $this->last_processed_invoice = $invoice->invoice_number;
                    
                    // Jika metode pembayaran manual (prorate), ubah ke otomatis
                    if ($isProrate) {
                        $this->metode_pembayaran = 'otomatis';
                        
                        // Hitung ulang total harga
                        $this->hitungTotalHarga();
                        
                        Log::info('Metode pembayaran diubah dari prorate ke otomatis', [
                            'pelanggan_id' => $this->pelanggan_id,
                            'total_harga_baru' => $this->total_harga_layanan_x_pajak
                        ]);
                    }
                    
                    // Simpan perubahan
                    $this->save();
                });
                
                Log::info('Pembayaran berhasil ditangani', [
                    'invoice_id' => $invoiceId,
                    'pelanggan_id' => $this->pelanggan_id,
                    'status_baru' => $this->user_status,
                    'tgl_jatuh_tempo_baru' => $this->tgl_jatuh_tempo,
                    'last_processed_invoice' => $this->last_processed_invoice,
                    'metode_pembayaran' => $this->metode_pembayaran
                ]);
                
                // Update status di Mikrotik
                try {
                    $mikrotikManager = app(\App\Services\MikrotikSubscriptionManager::class);
                    $mikrotikManager->handleSubscriptionStatus($this, 'activate');
                } catch (\Exception $e) {
                    Log::error('Gagal mengaktifkan user di Mikrotik', [
                        'pelanggan_id' => $this->pelanggan_id,
                        'error' => $e->getMessage()
                    ]);
                }
                
                return true;
            } catch (\Exception $e) {
                Log::error('Gagal menangani pembayaran', [
                    'invoice_id' => $invoiceId,
                    'pelanggan_id' => $this->pelanggan_id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return false;
            }
        }
        
        return false;
    }
}