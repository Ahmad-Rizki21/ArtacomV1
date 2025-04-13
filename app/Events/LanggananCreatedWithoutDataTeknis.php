<?php

namespace App\Events;

use App\Models\Langganan;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LanggananCreatedWithoutDataTeknis
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $langganan;

    public function __construct(Langganan $langganan)
    {
        $this->langganan = $langganan;
    }
}