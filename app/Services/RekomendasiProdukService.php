<?php

namespace App\Services;

use App\Models\Pengguna;
use App\Models\Produk;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RekomendasiProdukService
{
    private function produkLayak(): Collection
    {
        return Produk::query()
            ->with('stok')
            ->where('status', 'aktif')
            ->where('harga', '>', 0)
            ->where('kode_produk', 'not like', 'AES1:%')
            ->where('nama_produk', 'not like', 'AES1:%')
            ->where('jenis', 'not like', 'AES1:%')
            ->where(function ($query) {
                $query->whereNull('motif')
                    ->orWhere('motif', 'not like', 'AES1:%');
            })
            ->where(function ($query) {
                $query->whereNull('warna')
                    ->orWhere('warna', 'not like', 'AES1:%');
            })
            ->where(function ($query) {
                $query->whereNull('material')
                    ->orWhere('material', 'not like', 'AES1:%');
            })
            ->get();
    }

    private function tokenisasi(string $teks): array
    {
        $teks = mb_strtolower($teks, 'UTF-8');

        return preg_split(
            '/[^\p{L}\p{N}]+/u',
            $teks,
            -1,
            PREG_SPLIT_NO_EMPTY
        ) ?: [];
    }

    private function tf(array $kata): array
    {
        if (empty($kata)) {
            return [];
        }

        $jumlah = count($kata);
        $frekuensi = array_count_values($kata);

        foreach ($frekuensi as $term => $nilai) {
            $frekuensi[$term] = $nilai / $jumlah;
        }

        return $frekuensi;
    }

    private function cosine(array $a, array $b): float
    {
        $dot = 0;
        $normA = 0;
        $normB = 0;

        foreach ($a as $term => $nilai) {
            $dot += $nilai * ($b[$term] ?? 0);
            $normA += $nilai ** 2;
        }

        foreach ($b as $nilai) {
            $normB += $nilai ** 2;
        }

        if ($normA == 0 || $normB == 0) {
            return 0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }

    public function rekomendasiUntuk(
        Pengguna $pengguna,
        int $limit = 4
    ): Collection {
        $kategori = $pengguna->preferensi()
            ->pluck('kategori')
            ->filter()
            ->unique()
            ->values();

        if ($kategori->isEmpty()) {
            return collect();
        }

        $produk = $this->produkLayak();

        if ($produk->isEmpty()) {
            return collect();
        }

        // Dokumen pengguna dan dokumen produk
        $dokumen = [
            $this->tokenisasi($kategori->implode(' '))
        ];

        foreach ($produk as $item) {
            $dokumen[] = $this->tokenisasi(implode(' ', [
                $item->jenis,
                $item->nama_produk,
                $item->motif,
                $item->warna,
                $item->material,
            ]));
        }

        // Document Frequency
        $df = [];

        foreach ($dokumen as $kata) {
            foreach (array_unique($kata) as $term) {
                $df[$term] = ($df[$term] ?? 0) + 1;
            }
        }

        // Inverse Document Frequency
        $idf = [];
        $n = count($dokumen);

        foreach ($df as $term => $frekuensi) {
            $idf[$term] = log(
                ($n + 1) / ($frekuensi + 1)
            ) + 1;
        }

        // Membentuk vektor TF-IDF
        $vektor = [];

        foreach ($dokumen as $kata) {
            $nilaiTf = $this->tf($kata);
            $hasil = [];

            foreach ($nilaiTf as $term => $nilai) {
                $hasil[$term] = $nilai * $idf[$term];
            }

            $vektor[] = $hasil;
        }

        $kategoriFavorit = $kategori
            ->map(fn ($nilai) =>
                mb_strtolower(trim($nilai), 'UTF-8')
            )
            ->all();

        $hasilRekomendasi = [];

        foreach ($produk as $index => $item) {
            $similarity = $this->cosine(
                $vektor[0],
                $vektor[$index + 1]
            );

            $sesuaiKategori = in_array(
                mb_strtolower(
                    trim((string) $item->jenis),
                    'UTF-8'
                ),
                $kategoriFavorit,
                true
            );

            // 60% kemiripan teks, 40% kategori
            $skor = (0.6 * $similarity)
                + (0.4 * ($sesuaiKategori ? 1 : 0));

            if ($skor > 0) {
                $hasilRekomendasi[] = [
                    'produk' => $item,
                    'skor' => $skor,
                ];
            }
        }

        usort(
            $hasilRekomendasi,
            fn ($a, $b) =>
                ($b['skor'] <=> $a['skor'])
                ?: ($a['produk']->id <=> $b['produk']->id)
        );

        return collect($hasilRekomendasi)
            ->take($limit)
            ->pluck('produk')
            ->values();
    }

    public function produkTerlaris(int $limit = 4): Collection
    {
        $produk = $this->produkLayak();

        if ($produk->isEmpty()) {
            return collect();
        }

        // Hitung penjualan hanya dari pembayaran berhasil.
        // EXISTS mencegah penghitungan ganda ketika satu
        // pesanan memiliki beberapa catatan pembayaran.
        $penjualan = DB::table('detail_pesanan as dp')
            ->select('dp.produk_id')
            ->selectRaw('SUM(dp.jumlah) AS total_terjual')
            ->whereIn(
                'dp.produk_id',
                $produk->pluck('id')->all()
            )
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('pembayaran as pb')
                    ->whereColumn(
                        'pb.pesanan_id',
                        'dp.pesanan_id'
                    )
                    ->where(
                        'pb.status_pembayaran',
                        'berhasil'
                    );
            })
            ->groupBy('dp.produk_id')
            ->pluck('total_terjual', 'produk_id');

        return $produk
            ->filter(
                fn ($item) =>
                    (int) ($penjualan[$item->id] ?? 0) > 0
            )
            ->sort(
                fn ($a, $b) =>
                    ((int) ($penjualan[$b->id] ?? 0)
                    <=> (int) ($penjualan[$a->id] ?? 0))
                    ?: ($a->id <=> $b->id)
            )
            ->take($limit)
            ->values();
    }
}