# Implementasi Sprint 1 - Fondasi Sistem

Implementasi mengikuti Sprint 1 Product Backlog: perancangan database/UML, registrasi-masuk dan hak akses, CRUD Produk, CRUD Penenun, serta REST API dasar.

## Fitur yang selesai

- Registrasi Pembeli dan masuk multi-peran (`admin`, `penenun`, `pembeli`).
- Middleware hak akses peran.
- CRUD **Produk & Stok** oleh Admin; saat produk dibuat, satu record `stok` otomatis dibuat dengan `jumlah_stok = 0`.
- CRUD data dan akun Penenun.
- Katalog publik membaca tabel `produk` + `stok`.
- REST API v1 berbasis Sanctum dengan endpoint produk dan penenun dasar.
- Skema database penuh untuk Sprint 1-4 telah disiapkan dengan nama domain Bahasa Indonesia.
- UI responsif calm-elegant tetap dipertahankan.

## Konsistensi istilah

Kode domain utama telah diubah ke Bahasa Indonesia: `Pengguna`, `Produk`, `Stok`, `ProfilPenenun`, `TargetProduksi`, `HasilProduksi`, `Keranjang`, `Pesanan`, `Pembayaran`, dan `MutasiStok`. Nama route, input form, validasi, variabel Blade, endpoint API, respons API, migration, seeder, dan dokumentasi juga diselaraskan.

## Batas Sprint 1

Tabel target produksi, hasil produksi, mutasi stok, keranjang, pesanan, detail pesanan, dan pembayaran sudah ada untuk menjaga desain database final, tetapi logika bisnisnya diaktifkan bertahap pada Sprint 2-4.
