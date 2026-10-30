<?php

namespace App\Services;

use Midtrans\Config;
use Midtrans\Snap;

class MidtransService
{
    public function __construct()
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = (bool) config('midtrans.is_production', false);
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    public function buatSnapToken(
        string $kodePesanan,
        int $totalHarga,
        string $namaPembeli,
        string $emailPembeli
    ): string {
        if ($totalHarga <= 0) {
            throw new \InvalidArgumentException(
                'Total pembayaran harus lebih dari nol.'
            );
        }

        return Snap::getSnapToken([
            'transaction_details' => [
                'order_id' => $kodePesanan,
                'gross_amount' => $totalHarga,
            ],
            'customer_details' => [
                'first_name' => $namaPembeli,
                'email' => $emailPembeli,
            ],
        ]);
    }
}