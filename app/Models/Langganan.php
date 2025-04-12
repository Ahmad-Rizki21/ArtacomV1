<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Services\MikrotikSubscriptionManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action as NotificationAction;

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

    // Relasi ke data teknis
    protected static function boot()
{
    parent::boot();

    static::creating(function ($langganan) {
        // Cek apakah pelanggan sudah memiliki langganan berdasarkan pelanggan_id
        $existingLanggananByPelanggan = self::where('pelanggan_id', $langganan->pelanggan_id)
                                          ->where('user_status', 'Aktif') // Diperbaiki dari 'status' ke 'user_status'
                                          ->first();

        // Cek apakah pelanggan sudah memiliki langganan berdasarkan id_pelanggan dari data_teknis
        $dataTeknis = DataTeknis::where('pelanggan_id', $langganan->pelanggan_id)->first();
        if ($dataTeknis && $dataTeknis->id_pelanggan) {
            $existingLanggananByIdPelanggan = self::where('id_pelanggan', $dataTeknis->id_pelanggan)
                                                ->where('user_status', 'Aktif') // Diperbaiki dari 'status' ke 'user_status'
                                                ->first();

            if ($existingLanggananByIdPelanggan) {
                $pelanggan = Pelanggan::find($langganan->pelanggan_id);
                $namaPelanggan = $pelanggan ? $pelanggan->nama : 'ID #' . $langganan->pelanggan_id;

                Log::warning('Mencoba membuat langganan duplikat berdasarkan id_pelanggan', [
                    'pelanggan_id' => $langganan->pelanggan_id,
                    'id_pelanggan' => $dataTeknis->id_pelanggan,
                    'existing_langganan_id' => $existingLanggananByIdPelanggan->id,
                    'nama_pelanggan' => $namaPelanggan
                ]);

                Notification::make()
                    ->warning()
                    ->title('Langganan Sudah Ada')
                    ->body("Pelanggan {$namaPelanggan} sudah memiliki langganan aktif berdasarkan ID Pelanggan #{$dataTeknis->id_pelanggan}. Satu pelanggan hanya boleh memiliki satu langganan aktif.")
                    ->persistent()
                    ->actions([
                        NotificationAction::make('lihat')
                            ->label('Lihat Langganan')
                            ->url(route('filament.resources.langganan.edit', $existingLanggananByIdPelanggan->id))
                            ->button(),
                    ])
                    ->send();

                throw ValidationException::withMessages([
                    'pelanggan_id' => ["Pelanggan {$namaPelanggan} sudah memiliki langganan aktif berdasarkan ID Pelanggan."],
                ]);
            }
        }

        if ($existingLanggananByPelanggan) {
            $pelanggan = Pelanggan::find($langganan->pelanggan_id);
            $namaPelanggan = $pelanggan ? $pelanggan->nama : 'ID #' . $langganan->pelanggan_id;

            Log::warning('Mencoba membuat langganan duplikat untuk pelanggan', [
                'pelanggan_id' => $langganan->pelanggan_id,
                'existing_langganan_id' => $existingLanggananByPelanggan->id,
                'nama_pelanggan' => $namaPelanggan
            ]);

            Notification::make()
                ->warning()
                ->title('Langganan Sudah Ada')
                ->body("Pelanggan {$namaPelanggan} sudah memiliki langganan aktif dengan ID #{$existingLanggananByPelanggan->id}. Satu pelanggan hanya boleh memiliki satu langganan aktif.")
                ->persistent()
                ->actions([
                    NotificationAction::make('lihat')
                        ->label('Lihat Langganan')
                        ->url(route('filament.resources.langganan.edit', $existingLanggananByPelanggan->id))
                        ->button(),
                ])
                ->send();

            throw ValidationException::withMessages([
                'pelanggan_id' => ["Pelanggan {$namaPelanggan} sudah memiliki langganan aktif di database."],
            ]);
        }

        // Lanjutkan logika lain seperti sebelumnya...
        $pelanggan = Pelanggan::find($langganan->pelanggan_id);
        if ($pelanggan) {
            if (empty($langganan->id_brand) && !empty($pelanggan->id_brand)) {
                $langganan->id_brand = $pelanggan->id_brand;
            }
            if (empty($langganan->layanan) && !empty($pelanggan->layanan)) {
                $langganan->layanan = $pelanggan->layanan;
            }
        }

        $dataTeknis = DataTeknis::where('pelanggan_id', $langganan->pelanggan_id)->first();
        if ($dataTeknis) {
            $langganan->profile_pppoe = $dataTeknis->profile_pppoe;
            $langganan->id_pelanggan = $dataTeknis->id_pelanggan;
            $langganan->olt = $dataTeknis->olt;
        }

        if ($langganan->id_brand) {
            $hargaLayanan = HargaLayanan::find($langganan->id_brand);
            if ($hargaLayanan) {
                if (!$langganan->layanan && $langganan->profile_pppoe) {
                    $matches = [];
                    if (preg_match('/(\d+)Mbps/', $langganan->profile_pppoe, $matches)) {
                        $langganan->layanan = $matches[1] . ' Mbps';
                    }
                }
                $langganan->hitungTotalHarga();
            }
        }

        if (is_null($langganan->tgl_jatuh_tempo)) {
            $langganan->setTanggalJatuhTempo();
        }

        $langganan->user_status = 'Suspend';
    });

    static::updating(function ($langganan) {
        if ($langganan->isDirty('tgl_jatuh_tempo')) {
            Log::info('Tanggal jatuh tempo diubah', [
                'pelanggan_id' => $langganan->pelanggan_id,
                'tanggal_lama' => $langganan->getOriginal('tgl_jatuh_tempo'),
                'tanggal_baru' => $langganan->tgl_jatuh_tempo
            ]);
            $langganan->cekStatusJatuhTempo();
        }
    });
}

    // Method hitungTotalHarga tetap dipertahankan
    public function hitungTotalHarga($isManual = false, $manualHarga = null)
    {
        if ($this->metode_pembayaran == 'manual' && $this->total_harga_layanan_x_pajak && !$isManual) {
            return $this->total_harga_layanan_x_pajak;
        }

        if ($isManual && $manualHarga !== null) {
            $this->total_harga_layanan_x_pajak = $manualHarga;
            return $this->total_harga_layanan_x_pajak;
        }

        if (!$this->id_brand) {
            return 0;
        }

        $hargaLayanan = HargaLayanan::find($this->id_brand);
        if ($hargaLayanan) {
            if (!$this->layanan && $this->profile_pppoe) {
                $matches = [];
                if (preg_match('/(\d+)Mbps/', $this->profile_pppoe, $matches)) {
                    $this->layanan = $matches[1] . ' Mbps';
                }
            }

            $harga = match ($this->layanan) {
                '10 Mbps' => $hargaLayanan->harga_10mbps,
                '20 Mbps' => $hargaLayanan->harga_20mbps,
                '30 Mbps' => $hargaLayanan->harga_30mbps,
                '50 Mbps' => $hargaLayanan->harga_50mbps,
                default => 0,
            };

            $pajak = floor(($hargaLayanan->pajak / 100) * $harga);
            $total = $harga + $pajak;
            $totalBulat = ceil($total / 1000) * 1000;

            if ($hargaLayanan->id_brand === 'ajn-01') {
                if ($this->layanan === '10 Mbps') $totalBulat = 150000;
                else if ($this->layanan === '20 Mbps') $totalBulat = 220890;
                else if ($this->layanan === '30 Mbps') $totalBulat = 248640;
                else if ($this->layanan === '50 Mbps') $totalBulat = 281940;
            }

            if ($hargaLayanan->id_brand === 'ajn-02') {
                if ($this->layanan === '10 Mbps') $totalBulat = 166500;
                else if ($this->layanan === '20 Mbps') $totalBulat = 231990;
                else if ($this->layanan === '30 Mbps') $totalBulat = 276390;
                else if ($this->layanan === '50 Mbps') $totalBulat = 321789;
            }

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
        if (!$this->tgl_jatuh_tempo) {
            return true;
        }

        $tanggalJatuhTempo = Carbon::parse($this->tgl_jatuh_tempo);

        if ($tanggalJatuhTempo->isPast()) {
            $oldStatus = $this->user_status;

            if ($oldStatus === 'Aktif') {
                $this->user_status = 'Suspend';
                $this->save();

                Log::info('Status langganan diubah menjadi Suspend karena tanggal jatuh tempo sudah lewat', [
                    'pelanggan_id' => $this->pelanggan_id,
                    'tgl_jatuh_tempo' => $this->tgl_jatuh_tempo,
                    'old_status' => $oldStatus
                ]);

                try {
                    $mikrotikManager = app(MikrotikSubscriptionManager::class);
                    $mikrotikManager->handleSubscriptionStatus($this, 'suspend');

                    Notification::make()
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

    public function updateTanggalJatuhTempo($invoiceDate = null, $invoiceNumber = null)
    {
        try {
            Log::info('Memperbarui tanggal jatuh tempo langganan', [
                'pelanggan_id' => $this->pelanggan_id,
                'tanggal_lama' => $this->tgl_jatuh_tempo,
                'tanggal_invoice' => $invoiceDate,
                'invoice_number' => $invoiceNumber
            ]);

            if (!empty($invoiceNumber) && !empty($this->last_processed_invoice) && $this->last_processed_invoice === $invoiceNumber) {
                Log::info('Invoice sudah diproses sebelumnya, melewati update', [
                    'pelanggan_id' => $this->pelanggan_id,
                    'invoice_number' => $invoiceNumber
                ]);
                return false;
            }

            if (empty($invoiceDate)) {
                $invoiceDate = now()->format('Y-m-d');
                Log::info('Tanggal invoice kosong, menggunakan tanggal hari ini', [
                    'pelanggan_id' => $this->pelanggan_id,
                    'tanggal_hari_ini' => $invoiceDate
                ]);
            }

            $tanggalInvoiceCarbon = Carbon::parse($invoiceDate);

            if (is_string($invoiceDate)) {
                $invoiceDate = Carbon::parse($invoiceDate)->format('Y-m-d');
            }

            $oldStatus = $this->user_status;

            $tanggalBerlangganan = $this->tgl_jatuh_tempo ? Carbon::parse($this->tgl_jatuh_tempo) : $tanggalInvoiceCarbon;
            $tanggalJatuhTempo = $tanggalBerlangganan->copy()->addMonthNoOverflow();

            $this->tgl_jatuh_tempo = $tanggalJatuhTempo;

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

            if (!empty($invoiceNumber)) {
                $this->last_processed_invoice = $invoiceNumber;
            }

            $this->user_status = 'Aktif';

            DB::transaction(function() {
                $this->save();
            });

            Log::info('Tanggal jatuh tempo berhasil diperbarui', [
                'pelanggan_id' => $this->pelanggan_id,
                'tanggal_baru' => $this->tgl_jatuh_tempo,
                'status_baru' => $this->user_status
            ]);

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

    public function updateStatus()
    {
        $latestInvoice = $this->invoices()->latest('created_at')->first();

        if ($latestInvoice) {
            $oldStatus = $this->user_status;

            if (in_array($latestInvoice->status_invoice, ['Lunas', 'Selesai'])) {
                $this->user_status = 'Aktif';
                $this->save();

                Log::info('Status langganan diperbarui menjadi Aktif', [
                    'pelanggan_id' => $this->pelanggan_id,
                    'invoice_number' => $latestInvoice->invoice_number
                ]);

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
                $this->user_status = 'Suspend';
                $this->save();

                Log::info('Status langganan diperbarui menjadi Suspend', [
                    'pelanggan_id' => $this->pelanggan_id,
                    'invoice_number' => $latestInvoice->invoice_number,
                    'invoice_status' => $latestInvoice->status_invoice
                ]);

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

    public function switchToAutomaticPayment()
    {
        $this->metode_pembayaran = 'otomatis';
        $this->hitungTotalHarga();
        $this->save();

        Log::info('Metode pembayaran diubah ke otomatis setelah prorate dibayar', [
            'pelanggan_id' => $this->pelanggan_id,
            'invoice_number' => $this->last_processed_invoice,
            'total_harga_baru' => $this->total_harga_layanan_x_pajak
        ]);

        return true;
    }

    public function handlePayment($invoiceId)
    {
        $invoice = Invoice::find($invoiceId);

        if (!$invoice) {
            Log::warning('Invoice tidak ditemukan', ['invoice_id' => $invoiceId]);
            return false;
        }

        if (in_array($invoice->status_invoice, ['Lunas', 'Selesai'])) {
            if (!empty($this->last_processed_invoice) && $this->last_processed_invoice === $invoice->invoice_number) {
                Log::info('Invoice sudah diproses, melewati update', [
                    'invoice_id' => $invoiceId,
                    'invoice_number' => $invoice->invoice_number
                ]);
                return false;
            }

            try {
                DB::transaction(function() use ($invoice) {
                    $isProrate = $this->metode_pembayaran === 'manual';

                    $this->user_status = 'Aktif';

                    if ($this->tgl_jatuh_tempo) {
                        $this->tgl_jatuh_tempo = Carbon::parse($this->tgl_jatuh_tempo)->addMonth();
                    } else {
                        $tanggalInvoice = $invoice->tgl_invoice ?? now();
                        $this->tgl_jatuh_tempo = Carbon::parse($tanggalInvoice)->addMonth();
                    }

                    if ($invoice->tgl_invoice) {
                        $this->tgl_invoice_terakhir = $invoice->tgl_invoice;
                    }

                    $this->last_processed_invoice = $invoice->invoice_number;

                    if ($isProrate) {
                        $this->metode_pembayaran = 'otomatis';
                        $this->hitungTotalHarga();

                        Log::info('Metode pembayaran diubah dari prorate ke otomatis', [
                            'pelanggan_id' => $this->pelanggan_id,
                            'total_harga_baru' => $this->total_harga_layanan_x_pajak
                        ]);
                    }

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