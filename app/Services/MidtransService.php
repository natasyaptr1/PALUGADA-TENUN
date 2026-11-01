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

    // Fungsi lama dipertahankan agar kode yang sudah
    // menggunakan buatSnapRedirectUrl tetap berjalan.
    public function buatSnapRedirectUrl(
        string $kodePesanan,
        int $totalHarga,
        string $namaPembeli,
        string $emailPembeli
    ): string {
        $transaksi = $this->buatSnapTransaction(
            $kodePesanan,
            $totalHarga,
            $namaPembeli,
            $emailPembeli
        );

        return $transaksi['redirect_url'];
    }

    // FUNGSI BARU:
    // Mengembalikan token dan URL pembayaran sekaligus.
    public function buatSnapTransaction(
        string $kodePesanan,
        int $totalHarga,
        string $namaPembeli,
        string $emailPembeli
    ): array {
        if ($totalHarga <= 0) {
            throw new \InvalidArgumentException(
                'Total pembayaran harus lebih dari nol.'
            );
        }

        $transaksi = Snap::createTransaction([
            'transaction_details' => [
                'order_id' => $kodePesanan,
                'gross_amount' => $totalHarga,
            ],
            'customer_details' => [
                'first_name' => $namaPembeli,
                'email' => $emailPembeli,
            ],
            'expiry' => [
                'start_time' => now()
                    ->timezone('Asia/Jakarta')
                    ->format('Y-m-d H:i:s O'),
                'unit' => 'day',
                'duration' => 1,
            ],
            'page_expiry' => [
                'unit' => 'day',
                'duration' => 1,
            ],
        ]);

        if (
            empty($transaksi->token) ||
            empty($transaksi->redirect_url)
        ) {
            throw new \RuntimeException(
                'Midtrans tidak mengembalikan token atau URL pembayaran.'
            );
        }

        return [
            'token' => $transaksi->token,
            'redirect_url' => $transaksi->redirect_url,
        ];
    }
}
