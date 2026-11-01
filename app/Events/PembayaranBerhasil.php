<?php

namespace App\Events;

use App\Models\Pesanan;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dipicu setiap kali pembayaran sebuah pesanan berhasil
 * (konfirmasi Cash oleh admin, atau webhook Midtrans).
 *
 * Listener (mis. pengurangan stok) harus berjalan sinkron (jangan di-queue)
 * dan idempotent, supaya kegagalan listener ikut membatalkan konfirmasi pembayaran.
 */
class PembayaranBerhasil
{
    use Dispatchable, SerializesModels;

    public function __construct(public Pesanan $pesanan)
    {
    }
}
