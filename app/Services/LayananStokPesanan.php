<?php

namespace App\Services;

use App\Models\MutasiStok;
use App\Models\Pesanan;
use App\Models\Stok;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LayananStokPesanan
{
    /**
     * Memvalidasi ketersediaan stok satu produk.
     *
     * Dipakai saat pembeli memilih jumlah produk
     * atau sebelum proses checkout.
     */
    public function validasiStok(int $produkId, int $jumlah): void
    {
        if ($jumlah < 1) {
            throw ValidationException::withMessages([
                'jumlah' => 'Jumlah produk harus minimal 1.',
            ]);
        }

        $stok = Stok::query()
            ->where('produk_id', $produkId)
            ->first();

        if (!$stok || $stok->jumlah_stok < $jumlah) {
            throw ValidationException::withMessages([
                'stok' => 'Stok produk tidak mencukupi.',
            ]);
        }
    }

    /**
     * Mengurangi stok setelah pembayaran berhasil.
     *
     * Fungsi ini hanya boleh dipanggil setelah
     * status pembayaran diverifikasi oleh sistem
     * pembayaran yang tepercaya.
     *
     * Seluruh proses menggunakan transaksi database
     * agar pengurangan stok dan pencatatan mutasi
     * berhasil atau gagal secara bersamaan.
     */
    public function kurangiSetelahPembayaranBerhasil(
        int $pesananId
    ): void {
        DB::transaction(function () use ($pesananId) {

            // Kunci pesanan untuk mencegah proses
            // bersamaan pada pesanan yang sama.
            $pesanan = Pesanan::query()
                ->whereKey($pesananId)
                ->lockForUpdate()
                ->firstOrFail();

            // Pastikan pembayaran sudah berhasil.
            $pembayaranBerhasil = $pesanan->pembayaran()
                ->where('status_pembayaran', 'berhasil')
                ->exists();

            if (!$pembayaranBerhasil) {
                throw ValidationException::withMessages([
                    'pembayaran' => 'Pembayaran belum berhasil.',
                ]);
            }

            // Ambil seluruh detail produk pesanan.
            $detailPesanan = $pesanan->detail()
                ->orderBy('produk_id')
                ->orderBy('id')
                ->get();

            if ($detailPesanan->isEmpty()) {
                throw ValidationException::withMessages([
                    'pesanan' => 'Pesanan tidak memiliki detail produk.',
                ]);
            }

            if ($detailPesanan->contains(
                fn ($detail) => $detail->jumlah < 1
            )) {
                throw ValidationException::withMessages([
                    'jumlah' => 'Jumlah produk dalam pesanan tidak valid.',
                ]);
            }

            // Gabungkan jumlah pembelian untuk produk
            // yang sama agar validasi stok akurat.
            $jumlahPerProduk = $detailPesanan
                ->groupBy('produk_id')
                ->map(function ($items) {
                    return $items->sum('jumlah');
                });

            // Kunci stok seluruh produk yang dibeli.
            $stokProduk = Stok::query()
                ->whereIn(
                    'produk_id',
                    $jumlahPerProduk->keys()
                )
                ->orderBy('produk_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('produk_id');

            // Periksa riwayat mutasi penjualan.
            $mutasiPenjualan = MutasiStok::query()
                ->whereIn(
                    'detail_pesanan_id',
                    $detailPesanan->pluck('id')
                )
                ->where('jenis_mutasi', 'keluar')
                ->where('sumber', 'penjualan')
                ->get();

            $mutasiPerDetail = $mutasiPenjualan
                ->groupBy('detail_pesanan_id');

            // Pastikan setiap detail sudah memiliki
            // tepat satu mutasi dengan data yang sesuai.
            $semuaSudahDiproses = $detailPesanan->every(
                function ($detail) use (
                    $mutasiPerDetail,
                    $stokProduk
                ) {
                    $mutasi = $mutasiPerDetail
                        ->get($detail->id);

                    $stok = $stokProduk
                        ->get($detail->produk_id);

                    if (
                        !$mutasi ||
                        $mutasi->count() !== 1 ||
                        !$stok
                    ) {
                        return false;
                    }

                    $catatan = $mutasi->first();

                    return
                        (int) $catatan->jumlah
                            === (int) $detail->jumlah
                        &&
                        (int) $catatan->stok_id
                            === (int) $stok->id;
                }
            );

            // Jika seluruh detail sudah diproses,
            // jangan kurangi stok untuk kedua kalinya.
            if ($semuaSudahDiproses) {
                return;
            }

            // Jika ada mutasi sebagian atau tidak cocok,
            // hentikan proses untuk mencegah kerusakan data.
            if ($mutasiPenjualan->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'stok' => 'Riwayat mutasi pesanan tidak konsisten.',
                ]);
            }

            // Validasi stok seluruh produk sebelum
            // melakukan pengurangan apa pun.
            foreach ($jumlahPerProduk as $produkId => $jumlah) {

                $stok = $stokProduk->get($produkId);

                if (!$stok || $stok->jumlah_stok < $jumlah) {
                    throw ValidationException::withMessages([
                        'stok' => 'Stok tidak mencukupi untuk produk ID '
                            . $produkId,
                    ]);
                }
            }

            // Kurangi stok dan simpan mutasi penjualan.
            foreach ($detailPesanan as $detail) {

                $stok = $stokProduk
                    ->get($detail->produk_id);

                $stok->decrement(
                    'jumlah_stok',
                    $detail->jumlah
                );

                MutasiStok::create([
                    'stok_id' => $stok->id,
                    'jenis_mutasi' => 'keluar',
                    'jumlah' => $detail->jumlah,
                    'sumber' => 'penjualan',
                    'hasil_produksi_id' => null,
                    'detail_pesanan_id' => $detail->id,
                    'catatan' => 'Penjualan pesanan '
                        . $pesanan->kode_pesanan,
                    'tanggal_mutasi' => now(),
                ]);
            }

            // Perbarui status pesanan jika masih
            // menunggu pembayaran.
            if (
                $pesanan->status_pesanan
                === 'menunggu_pembayaran'
            ) {
                $pesanan->update([
                    'status_pesanan' => 'dibayar',
                ]);
            }

        }, 3);
    }
}
