<?php

namespace App\Events;

use App\Models\Langganan;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LanggananStatusChanged
{
    use Dispatchable, SerializesModels;

    public $langganan;

    public function __construct(Langganan $langganan)
    {
        $this->langganan = $langganan;
    }
}
