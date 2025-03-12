<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
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
            // Cek metode pembayaran
            $metodePembayaran = request()->input('metode_pembayaran');

            // Ambil data teknis terkait pelanggan
            $dataTeknis = $langganan->pelanggan->dataTeknis;

            if ($dataTeknis) {
                // Salin data teknis ke dalam langganan
                $langganan->profile_pppoe = $dataTeknis->profile_pppoe;
                $langganan->id_pelanggan = $dataTeknis->id_pelanggan;
                $langganan->olt = $dataTeknis->olt;
            }
            
            if ($metodePembayaran === 'manual') {
                // Gunakan harga manual
                $manualHarga = request()->input('total_harga_layanan_x_pajak');
                if ($manualHarga) {
                    $langganan->total_harga_layanan_x_pajak = $manualHarga;
                }
            } else {
                // Hitung otomatis - pastikan hitung selalu dilakukan saat otomatis
                $langganan->hitungTotalHarga();
                
                // Log nilai setelah perhitungan
                Log::info('Total Harga Setelah Perhitungan di Model', [
                    'total_harga' => $langganan->total_harga_layanan_x_pajak
                ]);
            }

            if (is_null($langganan->tgl_jatuh_tempo)) {
                $langganan->setTanggalJatuhTempo();
            }

            // Set status awal langganan ke Suspend
            $langganan->user_status = 'Suspend';
        });
    }

    public function hitungTotalHarga($isManual = false, $manualHarga = null)
    {
        if ($isManual && $manualHarga !== null) {
            $this->total_harga_layanan_x_pajak = $manualHarga;
            return $manualHarga;
        }

        $hargaLayanan = HargaLayanan::where('id_brand', $this->id_brand)->first();
        if ($hargaLayanan) {
            $harga = match ($this->layanan) {
                '10 Mbps' => $hargaLayanan->harga_10mbps,
                '20 Mbps' => $hargaLayanan->harga_20mbps,
                '30 Mbps' => $hargaLayanan->harga_30mbps,
                '50 Mbps' => $hargaLayanan->harga_50mbps,
                default => 0,
            };

            $pajak = ($hargaLayanan->pajak / 100) * $harga;
            $this->total_harga_layanan_x_pajak = $harga + $pajak;
            return $this->total_harga_layanan_x_pajak;
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

    /**
     * Update tanggal jatuh tempo setelah pembayaran
     * Method ini dipanggil dari Invoice setelah pembayaran berhasil
     * @param string|null $invoiceDate Tanggal invoice yang dibayar
     */
    public function updateTanggalJatuhTempo($invoiceDate = null)
    {
        // Log informasi untuk debugging
        Log::info('Memperbarui tanggal jatuh tempo langganan', [
            'pelanggan_id' => $this->pelanggan_id,
            'tanggal_lama' => $this->tgl_jatuh_tempo,
            'tanggal_invoice' => $invoiceDate
        ]);

        // Cek jika tanggal jatuh tempo ada
        if ($this->tgl_jatuh_tempo) {
            $tanggalSekarang = Carbon::parse($this->tgl_jatuh_tempo);
            
            // Ambil tanggal yang sama di bulan berikutnya
            $tanggalBaru = $tanggalSekarang->copy()->addMonth();
            
            // Update tanggal jatuh tempo
            $this->tgl_jatuh_tempo = $tanggalBaru;
            
            // Update tanggal invoice terakhir jika ada
            if ($invoiceDate) {
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
            
            // Update juga status pengguna menjadi Aktif
            $this->user_status = 'Aktif';
            
            // Simpan perubahan
            $this->save();
            
            Log::info('Tanggal jatuh tempo diperbarui', [
                'pelanggan_id' => $this->pelanggan_id,
                'tanggal_baru' => $this->tgl_jatuh_tempo,
                'status_baru' => $this->user_status,
                'tgl_invoice_terakhir' => $this->tgl_invoice_terakhir
            ]);
            
            return true;
        }
        
        Log::warning('Gagal update tanggal jatuh tempo: Tanggal tidak ditemukan', [
            'pelanggan_id' => $this->pelanggan_id
        ]);
        
        return false;
    }

    /**
     * Update status langganan berdasarkan status invoice terakhir
     */
    public function updateStatus()
    {
        // Ambil invoice terakhir untuk pelanggan ini
        $latestInvoice = $this->invoices()->latest('created_at')->first();

        if ($latestInvoice) {
            // Jika status invoice adalah Lunas atau Selesai, update status langganan menjadi Aktif
            if (in_array($latestInvoice->status_invoice, ['Lunas', 'Selesai'])) {
                $this->user_status = 'Aktif';
                $this->save();
                
                Log::info('Status langganan diperbarui menjadi Aktif', [
                    'pelanggan_id' => $this->pelanggan_id,
                    'invoice_number' => $latestInvoice->invoice_number
                ]);
                
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
                
                return true;
            }
        }
        
        return false;
    }

    /**
     * Fungsi untuk menangani pembayaran invoice
     * Setelah invoice dibayar, update status langganan dan tanggal jatuh tempo
     */
    public function handlePayment($invoiceId)
    {
        $invoice = Invoice::find($invoiceId);
        
        if ($invoice && in_array($invoice->status_invoice, ['Lunas', 'Selesai'])) {
            // Update status langganan menjadi Aktif
            $this->user_status = 'Aktif';
            
            // Update tanggal jatuh tempo ke bulan berikutnya (dari tanggal yang sama)
            if ($this->tgl_jatuh_tempo) {
                $this->tgl_jatuh_tempo = Carbon::parse($this->tgl_jatuh_tempo)->addMonth();
            } else {
                // Jika tidak ada tanggal jatuh tempo, gunakan tanggal invoice + 1 bulan
                $this->tgl_jatuh_tempo = Carbon::parse($invoice->tgl_invoice)->addMonth();
            }
            
            // Simpan perubahan
            $this->save();
            
            Log::info('Pembayaran berhasil ditangani', [
                'invoice_id' => $invoiceId,
                'pelanggan_id' => $this->pelanggan_id,
                'status_baru' => $this->user_status,
                'tgl_jatuh_tempo_baru' => $this->tgl_jatuh_tempo
            ]);
            
            return true;
        }
        
        return false;
    }
}