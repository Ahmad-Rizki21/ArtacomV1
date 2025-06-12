<?php

namespace App\Events;

use App\Models\Pelanggan;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PelangganCreated
{
    use Dispatchable, SerializesModels;

    public $pelanggan;

    public function __construct(Pelanggan $pelanggan)
    {
        $this->pelanggan = $pelanggan;
    }
}
