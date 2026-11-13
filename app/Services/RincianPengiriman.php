<?php

namespace App\Services;

use App\Enums\MetodePengiriman;

readonly class RincianPengiriman
{
    /**
     * Buat instance value object RincianPengiriman.
     */
    public function __construct(
        public MetodePengiriman $metodePengiriman,
        public string $metodePembayaran,
        public float $subtotal,
        public int $ongkir,
        public float $total,
        public ?string $kurirKode,
        public ?string $kurirNama,
        public ?string $layananKurir,
        public ?string $estimasiKirim,
        public ?string $tujuanId,
        public ?string $tujuanLabel,
        public int $beratTotalGram,
        public string $alamatPengiriman,
    ) {
    }

    /**
     * Konversi ke array atribut pesanan yang persis sesuai kolom tabel pesanan.
     *
     * @return array{
     *     subtotal: float,
     *     ongkir: int,
     *     total: float,
     *     metode_pengiriman: string,
     *     kurir_kode: ?string,
     *     kurir_nama: ?string,
     *     layanan_kurir: ?string,
     *     estimasi_kirim: ?string,
     *     tujuan_id: ?string,
     *     tujuan_label: ?string,
     *     berat_total_gram: int,
     *     alamat_pengiriman: string
     * }
     */
    public function keAtributPesanan(): array
    {
        return [
            'subtotal' => $this->subtotal,
            'ongkir' => $this->ongkir,
            'total' => $this->total,
            'metode_pengiriman' => $this->metodePengiriman->value,
            'kurir_kode' => $this->kurirKode,
            'kurir_nama' => $this->kurirNama,
            'layanan_kurir' => $this->layananKurir,
            'estimasi_kirim' => $this->estimasiKirim,
            'tujuan_id' => $this->tujuanId,
            'tujuan_label' => $this->tujuanLabel,
            'berat_total_gram' => $this->beratTotalGram,
            'alamat_pengiriman' => $this->alamatPengiriman,
        ];
    }

    /**
     * Total nilai pesanan dibulatkan ke rupiah terdekat (untuk Midtrans).
     */
    public function totalRupiah(): int
    {
        return (int) round($this->total);
    }

    /**
     * Bandingkan apakah rincian ini cocok dengan subtotal dan berat hasil perhitungan ulang.
     * Toleransi 0,01 untuk subtotal; berat harus sama persis.
     */
    public function cocokDengan(float $subtotal, int $beratGram): bool
    {
        return abs($this->subtotal - $subtotal) <= 0.01 && $this->beratTotalGram === $beratGram;
    }
}

