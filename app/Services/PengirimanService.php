<?php

namespace App\Services;

use App\Enums\MetodePengiriman;
use App\Exceptions\OngkirException;
use Illuminate\Validation\ValidationException;

class PengirimanService
{
    /**
     * Buat instance PengirimanService dengan dependency injection OngkirService.
     */
    public function __construct(
        protected OngkirService $ongkirService
    ) {
    }

    /**
     * Siapkan dan validasi rincian pengiriman untuk pesanan pembeli.
     *
     * @param array<string, mixed> $input
     * @param float $subtotal
     * @param int $beratTotalGram
     * @return RincianPengiriman
     * @throws ValidationException
     */
    public function siapkan(array $input, float $subtotal, int $beratTotalGram): RincianPengiriman
    {
        // 1. Validasi subtotal
        if ($subtotal <= 0) {
            throw ValidationException::withMessages([
                'subtotal' => 'Subtotal belanja harus lebih besar dari 0.',
                'ongkir' => 'Subtotal belanja harus lebih besar dari 0.',
            ]);
        }

        // 2. Validasi metode pengiriman
        $rawMetodePengiriman = $input['metode_pengiriman'] ?? null;
        if ($rawMetodePengiriman instanceof MetodePengiriman) {
            $metodePengiriman = $rawMetodePengiriman;
        } elseif (is_string($rawMetodePengiriman)) {
            $metodePengiriman = MetodePengiriman::tryFrom($rawMetodePengiriman);
        } else {
            $metodePengiriman = null;
        }

        if ($metodePengiriman === null) {
            throw ValidationException::withMessages([
                'metode_pengiriman' => 'Metode pengiriman yang dipilih tidak valid.',
            ]);
        }

        // 3. Validasi metode pembayaran
        $metodeBayar = strtolower(trim((string) ($input['metode'] ?? '')));
        $diizinkan = $this->pembayaranDiizinkan($metodePengiriman);

        if ($metodeBayar === '' || !in_array($metodeBayar, $diizinkan, true)) {
            $daftarLabel = array_map(function (string $m): string {
                if ($m === 'cash') {
                    return 'Cash';
                }
                if ($m === 'midtrans') {
                    return 'online (Midtrans)';
                }
                return ucfirst($m);
            }, $diizinkan);

            $opsiBayarTeks = implode(' atau ', $daftarLabel);

            if ($metodePengiriman === MetodePengiriman::AmbilDiTempat) {
                $pesan = "Pembayaran untuk ambil di tempat hanya mendukung {$opsiBayarTeks}.";
            } else {
                $pesan = "Pengiriman ekspedisi harus dibayar melalui {$opsiBayarTeks}.";
            }

            throw ValidationException::withMessages([
                'metode' => $pesan,
            ]);
        }

        // 4. Jalur 1: Ambil Di Tempat
        if ($metodePengiriman === MetodePengiriman::AmbilDiTempat) {
            $ongkir = 0;
            $total = round($subtotal, 2);
            $alamatPengiriman = (string) config('ongkir.label_ambil_di_tempat', 'Ambil di tempat (Palugada Bandar)');

            return new RincianPengiriman(
                metodePengiriman: $metodePengiriman,
                metodePembayaran: $metodeBayar,
                subtotal: round($subtotal, 2),
                ongkir: $ongkir,
                total: $total,
                kurirKode: null,
                kurirNama: null,
                layananKurir: null,
                estimasiKirim: null,
                tujuanId: null,
                tujuanLabel: null,
                beratTotalGram: $beratTotalGram,
                alamatPengiriman: $alamatPengiriman,
            );
        }

        // 5. Jalur 2: Ekspedisi
        // Validasi tujuan_id
        $tujuanId = trim((string) ($input['tujuan_id'] ?? ''));
        if ($tujuanId === '' || !ctype_digit($tujuanId) || strlen($tujuanId) > 30) {
            throw ValidationException::withMessages([
                'tujuan_id' => 'Tujuan pengiriman wajib dipilih berupa ID wilayah yang valid (angka).',
            ]);
        }

        // Validasi kurir_kode
        $kurirKode = trim((string) ($input['kurir_kode'] ?? ''));
        if ($kurirKode === '') {
            throw ValidationException::withMessages([
                'kurir' => 'Kurir pengiriman wajib dipilih.',
            ]);
        }

        // Validasi layanan_kurir
        $layananKurir = trim((string) ($input['layanan_kurir'] ?? ''));
        if ($layananKurir === '') {
            throw ValidationException::withMessages([
                'kurir' => 'Layanan kurir wajib dipilih.',
            ]);
        }

        // Validasi alamat_pengiriman
        $alamatPengiriman = trim((string) ($input['alamat_pengiriman'] ?? ''));
        if ($alamatPengiriman === '' || mb_strlen($alamatPengiriman) > 1000) {
            throw ValidationException::withMessages([
                'alamat_pengiriman' => 'Alamat pengiriman wajib diisi lengkap (maksimal 1000 karakter).',
            ]);
        }

        // Validasi berat
        if ($beratTotalGram < 1) {
            throw ValidationException::withMessages([
                'ongkir' => 'Berat total paket minimal 1 gram untuk pengiriman ekspedisi.',
                'berat' => 'Berat total paket minimal 1 gram untuk pengiriman ekspedisi.',
            ]);
        }

        // Panggil temukanOpsi ke OngkirService
        try {
            $opsi = $this->ongkirService->temukanOpsi($tujuanId, $beratTotalGram, $kurirKode, $layananKurir);
        } catch (OngkirException $e) {
            $pesan = $e->getMessage() ?: 'Ongkos kirim tidak dapat dihitung saat ini. Coba lagi atau pilih ambil di tempat.';
            throw ValidationException::withMessages([
                'ongkir' => $pesan,
            ]);
        }

        if ($opsi === null) {
            throw ValidationException::withMessages([
                'kurir' => 'Layanan pengiriman yang dipilih tidak tersedia lagi. Silakan pilih ulang.',
            ]);
        }

        $ongkir = (int) $opsi['biaya'];
        $kurirNama = (string) $opsi['kurir_nama'];
        $estimasiKirim = (string) $opsi['estimasi'];
        $total = round($subtotal + $ongkir, 2);

        // Bersihkan dan format tujuan_label
        $rawLabel = (string) ($input['tujuan_label'] ?? '');
        $rawLabel = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $rawLabel) ?? $rawLabel;
        $rawLabel = strip_tags($rawLabel);
        $rawLabel = trim(preg_replace('/\s+/', ' ', $rawLabel) ?? '');
        if ($rawLabel === '') {
            $tujuanLabel = "Tujuan #{$tujuanId}";
        } else {
            $tujuanLabel = mb_substr($rawLabel, 0, 255);
        }

        return new RincianPengiriman(
            metodePengiriman: $metodePengiriman,
            metodePembayaran: $metodeBayar,
            subtotal: round($subtotal, 2),
            ongkir: $ongkir,
            total: $total,
            kurirKode: $kurirKode,
            kurirNama: $kurirNama,
            layananKurir: $layananKurir,
            estimasiKirim: $estimasiKirim,
            tujuanId: $tujuanId,
            tujuanLabel: $tujuanLabel,
            beratTotalGram: $beratTotalGram,
            alamatPengiriman: $alamatPengiriman,
        );
    }

    /**
     * Dapatkan daftar metode pembayaran yang diizinkan untuk suatu metode pengiriman.
     *
     * @param MetodePengiriman $metode
     * @return array<int, string>
     */
    public function pembayaranDiizinkan(MetodePengiriman $metode): array
    {
        return (array) (config("ongkir.pembayaran_per_metode.{$metode->value}") ?? []);
    }

    /**
     * Hitung total berat dalam gram dan subtotal harga dari sekumpulan DetailKeranjang.
     *
     * @param iterable<\App\Models\DetailKeranjang> $detailKeranjang
     * @return array{berat_gram: int, subtotal: float}
     */
    public function ringkasKeranjang(iterable $detailKeranjang): array
    {
        $beratDefault = (int) config('ongkir.berat_default_gram', 500);
        $totalBerat = 0;
        $totalSubtotal = 0.0;

        foreach ($detailKeranjang as $detail) {
            $produk = $detail->produk ?? null;

            if ($produk === null) {
                continue;
            }

            if (method_exists($produk, 'trashed') && $produk->trashed()) {
                continue;
            }

            if (($produk->status ?? '') !== 'aktif') {
                continue;
            }

            $jumlah = max(1, (int) ($detail->jumlah ?? 1));

            $beratSatuan = isset($produk->berat_gram) && (int) $produk->berat_gram > 0
                ? (int) $produk->berat_gram
                : $beratDefault;

            $hargaSatuan = (float) ($produk->harga ?? 0);

            $totalBerat += ($beratSatuan * $jumlah);
            $totalSubtotal += ($hargaSatuan * $jumlah);
        }

        return [
            'berat_gram' => $totalBerat,
            'subtotal' => round($totalSubtotal, 2),
        ];
    }
}
