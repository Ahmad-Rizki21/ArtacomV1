<?php

namespace App\Notifications;

use App\Models\Langganan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SubscriptionSuspendedNotification extends Notification
{
    use Queueable;

    protected $langganan;
    protected $reason;

    public function __construct(Langganan $langganan, string $reason = 'unpaid_invoice')
    {
        $this->langganan = $langganan;
        $this->reason = $reason;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        $pelanggan = $this->langganan->pelanggan;
        $reasonText = $this->reason == 'unpaid_invoice' 
            ? 'memiliki invoice yang belum dibayar menjelang tanggal jatuh tempo' 
            : 'telah melewati tanggal jatuh tempo';

        return [
            'icon' => 'heroicon-o-exclamation-circle',
            'color' => 'danger',
            'title' => 'Langganan Disuspend',
            'body' => "Langganan {$pelanggan->nama} telah otomatis disuspend karena {$reasonText}.",
            'actions' => [
                [
                    'name' => 'Lihat Langganan',
                    'url' => "/admin/langganan/{$this->langganan->id}/edit",
                ],
            ],
        ];
    }
}