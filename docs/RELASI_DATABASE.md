# Relasi Database

Gambaran relasi final:

```text
PENGGUNA
├── 1 : 0..1 PROFIL_PENENUN
├── 1 : N TARGET_PRODUKSI        (sebagai penenun)
├── 1 : N KERANJANG              (sebagai pembeli)
├── 1 : N PESANAN                (sebagai pembeli)
└── 1 : N PEMBAYARAN             (sebagai admin pengonfirmasi Cash, opsional)

PRODUK
├── 1 : 1 STOK
├── 1 : N TARGET_PRODUKSI
├── 1 : N DETAIL_KERANJANG
└── 1 : N DETAIL_PESANAN

TARGET_PRODUKSI
└── 1 : N HASIL_PRODUKSI

STOK
└── 1 : N MUTASI_STOK

KERANJANG
├── 1 : N DETAIL_KERANJANG
└── 1 : 0..1 PESANAN

PESANAN
├── 1 : N DETAIL_PESANAN
└── 1 : N PEMBAYARAN

HASIL_PRODUKSI
└── 1 : N MUTASI_STOK (opsional sebagai sumber stok masuk)

DETAIL_PESANAN
└── 1 : N MUTASI_STOK (opsional sebagai sumber stok keluar)
```

## Alur data utama

Produksi: `produk + penenun -> target_produksi -> hasil_produksi -> stok -> mutasi_stok`.

Penjualan: `pengguna/pembeli -> keranjang -> detail_keranjang -> pesanan -> detail_pesanan -> pembayaran`, lalu transaksi berhasil menjadi sumber mutasi stok keluar.

Tidak ada tabel `katalog`, `laporan`, `riwayat_produksi`, atau `riwayat_pesanan`, karena semuanya merupakan tampilan/query dari tabel utama.
