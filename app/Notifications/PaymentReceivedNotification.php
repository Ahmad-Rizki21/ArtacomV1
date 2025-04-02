<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PaymentReceivedNotification extends Notification
{
    use Queueable;

    protected $invoice;
    protected $profileChanged;

    public function __construct(Invoice $invoice, bool $profileChanged = false)
    {
        $this->invoice = $invoice;
        $this->profileChanged = $profileChanged;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        $pelanggan = $this->invoice->pelanggan;
        $amount = number_format($this->invoice->total_harga, 0, ',', '.');
        
        $statusInfo = $this->profileChanged 
            ? " Status PPPoE berubah dari Suspend menjadi Aktif."
            : "";

        return [
            'icon' => 'heroicon-o-check-circle',
            'color' => 'success',
            'title' => 'Pembayaran Diterima',
            'body' => "Pelanggan {$pelanggan->nama} telah membayar invoice #{$this->invoice->invoice_number} sejumlah Rp {$amount}.{$statusInfo}",
            'actions' => [
                [
                    'name' => 'Lihat Invoice',
                    'url' => "/admin/invoices/{$this->invoice->id}",
                ],
            ],
        ];
    }
}