<?php

namespace App\Support;

class LabelPesanan
{
    /**
     * Daftar status pesanan yang valid di database.
     */
    public const STATUS_PESANAN_VALID = [
        'menunggu_pembayaran',
        'dibayar',
        'diproses',
        'selesai',
        'batal',
    ];

    /**
     * Normalisasi nilai status filter dari input request.
     * Mengembalikan status valid database atau null jika tidak dikenal/semua.
     */
    public static function normalisasiStatusFilter(?string $status): ?string
    {
        if ($status === null || trim($status) === '') {
            return null;
        }

        $status = strtolower(trim($status));

        if ($status === 'dibatalkan' || $status === 'batal') {
            return 'batal';
        }

        if (in_array($status, self::STATUS_PESANAN_VALID, true)) {
            return $status;
        }

        return null;
    }

    /**
     * Mendapatkan label human-readable untuk status pesanan.
     */
    public static function labelStatusPesanan(?string $status): string
    {
        return match ($status) {
            'menunggu_pembayaran' => 'Menunggu Pembayaran',
            'dibayar' => 'Dibayar',
            'diproses' => 'Diproses',
            'selesai' => 'Selesai',
            'batal', 'dibatalkan' => 'Dibatalkan',
            default => $status ? ucfirst(str_replace('_', ' ', $status)) : '-',
        };
    }

    /**
     * Mendapatkan class CSS badge untuk status pesanan.
     */
    public static function classStatusPesanan(?string $status): string
    {
        return match ($status) {
            'menunggu_pembayaran' => 'status-pesanan-menunggu-pembayaran',
            'dibayar' => 'status-pesanan-dibayar',
            'diproses' => 'status-pesanan-diproses',
            'selesai' => 'status-pesanan-selesai',
            'batal', 'dibatalkan' => 'status-pesanan-batal',
            default => 'status-pesanan-netral',
        };
    }

    /**
     * Mendapatkan label human-readable untuk status pembayaran.
     */
    public static function labelStatusPembayaran(?string $status): string
    {
        return match ($status) {
            'menunggu' => 'Menunggu',
            'berhasil' => 'Berhasil',
            'gagal' => 'Gagal',
            'kedaluwarsa' => 'Kedaluwarsa',
            default => $status ? ucfirst(str_replace('_', ' ', $status)) : '-',
        };
    }

    /**
     * Mendapatkan class CSS badge untuk status pembayaran.
     */
    public static function classStatusPembayaran(?string $status): string
    {
        return match ($status) {
            'menunggu' => 'status-bayar-menunggu',
            'berhasil' => 'status-bayar-berhasil',
            'gagal' => 'status-bayar-gagal',
            'kedaluwarsa' => 'status-bayar-kedaluwarsa',
            default => 'status-bayar-netral',
        };
    }

    /**
     * Label metode pembayaran.
     */
    public static function labelMetodePembayaran(?string $metode): string
    {
        return match (strtolower((string) $metode)) {
            'cash' => 'Cash',
            'midtrans' => 'Midtrans',
            default => $metode ? ucfirst($metode) : '-',
        };
    }

    /**
     * Format rupiah.
     */
    public static function formatRupiah(float|int|string|null $nominal): string
    {
        return 'Rp ' . number_format((float) ($nominal ?? 0), 0, ',', '.');
    }
}
