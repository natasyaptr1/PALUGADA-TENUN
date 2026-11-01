<?php

namespace App\Support;

class AlurStatusPesanan
{
    public const AKSI_PROSES = 'proses';
    public const AKSI_SELESAI = 'selesai';
    public const AKSI_BATALKAN = 'batalkan';

    public const AKSI_VALID = [
        self::AKSI_PROSES,
        self::AKSI_SELESAI,
        self::AKSI_BATALKAN,
    ];

    /**
     * Definisi aturan transisi status operasional pesanan.
     */
    public const ATURAN = [
        self::AKSI_PROSES => [
            'status_asal' => 'dibayar',
            'status_tujuan' => 'diproses',
            'label_tombol' => 'Proses Pesanan',
            'kelas_tombol' => 'btn btn-primary',
            'pesan_sukses' => 'Pesanan berhasil diproses.',
            'format_konfirmasi' => 'Apakah Anda yakin ingin memproses pesanan %s?',
        ],
        self::AKSI_SELESAI => [
            'status_asal' => 'diproses',
            'status_tujuan' => 'selesai',
            'label_tombol' => 'Tandai Selesai',
            'kelas_tombol' => 'btn btn-primary',
            'pesan_sukses' => 'Pesanan ditandai selesai.',
            'format_konfirmasi' => 'Apakah Anda yakin ingin menandai pesanan %s selesai?',
        ],
        self::AKSI_BATALKAN => [
            'status_asal' => 'menunggu_pembayaran',
            'status_tujuan' => 'batal',
            'label_tombol' => 'Batalkan Pesanan',
            'kelas_tombol' => 'btn btn-danger-action',
            'pesan_sukses' => 'Pesanan berhasil dibatalkan.',
            'format_konfirmasi' => 'Apakah Anda yakin ingin membatalkan pesanan %s?',
        ],
    ];

    /**
     * Mengambil aksi yang tersedia untuk sebuah status pesanan saat ini.
     * Mengembalikan key aksi (mis. 'proses') atau null jika tidak ada aksi tersedia.
     */
    public static function aksiTersediaUntukStatus(?string $status): ?string
    {
        if (!$status) {
            return null;
        }

        foreach (self::ATURAN as $aksi => $config) {
            if ($config['status_asal'] === $status) {
                return $aksi;
            }
        }

        return null;
    }

    /**
     * Memeriksa apakah suatu aksi diizinkan dari status saat ini.
     */
    public static function boleh(string $aksi, ?string $statusSaatIni): bool
    {
        if (!isset(self::ATURAN[$aksi]) || !$statusSaatIni) {
            return false;
        }

        return self::ATURAN[$aksi]['status_asal'] === $statusSaatIni;
    }

    /**
     * Mengambil status tujuan dari aksi tertentu.
     */
    public static function statusTujuan(string $aksi): ?string
    {
        return self::ATURAN[$aksi]['status_tujuan'] ?? null;
    }

    /**
     * Mengambil pesan sukses untuk aksi tertentu.
     */
    public static function pesanSukses(string $aksi): string
    {
        return self::ATURAN[$aksi]['pesan_sukses'] ?? 'Status pesanan berhasil diperbarui.';
    }

    /**
     * Mengambil label tombol untuk aksi tertentu.
     */
    public static function labelTombol(string $aksi): string
    {
        return self::ATURAN[$aksi]['label_tombol'] ?? ucfirst($aksi);
    }

    /**
     * Mengambil kelas tombol untuk aksi tertentu.
     */
    public static function kelasTombol(string $aksi): string
    {
        return self::ATURAN[$aksi]['kelas_tombol'] ?? 'btn btn-primary';
    }

    /**
     * Format teks konfirmasi dengan kode pesanan.
     */
    public static function teksKonfirmasi(string $aksi, string $kodePesanan): string
    {
        $format = self::ATURAN[$aksi]['format_konfirmasi'] ?? 'Apakah Anda yakin ingin memperbarui pesanan %s?';
        return sprintf($format, $kodePesanan);
    }
}
