<?php

namespace App\Listeners;

use App\Events\PembayaranBerhasil;
use App\Services\LayananStokPesanan;

class KurangiStokSetelahPembayaran
{
    public function __construct(
        private LayananStokPesanan $layananStok
    ) {
    }

    public function handle(PembayaranBerhasil $event): void
    {
        $this->layananStok
            ->kurangiSetelahPembayaranBerhasil(
                $event->pesanan->id
            );
    }
}
