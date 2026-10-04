# Rancangan Database Final - Bahasa Indonesia

Semua **tabel dan atribut domain bisnis** menggunakan Bahasa Indonesia. Tabel teknis `personal_access_tokens` serta kolom konvensi Laravel `created_at`, `updated_at`, `deleted_at`, dan `remember_token` dipertahankan agar integrasi framework tetap aman dan sederhana.

## Tabel domain

1. `pengguna` - akun Admin, Penenun, dan Pembeli.
2. `profil_penenun` - data khusus penenun yang tidak dimiliki pengguna lain.
3. `produk` - master informasi produk tenun.
4. `target_produksi` - target produk untuk penenun.
5. `hasil_produksi` - pencatatan produk selesai per target.
6. `stok` - saldo stok terkini per produk (1 produk = 1 stok).
7. `mutasi_stok` - riwayat stok masuk, keluar, dan penyesuaian.
8. `keranjang` - keranjang milik pembeli.
9. `detail_keranjang` - produk dan jumlah di keranjang.
10. `pesanan` - transaksi hasil checkout; setiap pesanan wajib berasal dari satu keranjang.
11. `detail_pesanan` - rincian produk, jumlah, harga historis, subtotal.
12. `pembayaran` - pembayaran Cash/Midtrans yang selalu terkait pesanan.

## Keputusan penting

- `produk` dan `stok` tetap dua tabel karena data master produk berbeda dari saldo stok, walau pada UI/Use Case tampil sebagai satu kelompok **Produk & Stok**.
- `keranjang_id` pada `pesanan` **wajib** dan `UNIQUE`, sesuai alur final: Katalog → Keranjang → Checkout/Buat Pesanan.
- `hasil_produksi` tidak menyimpan `penenun_id` lagi karena penenun sudah ditentukan oleh `target_produksi`.
- `mutasi_stok` memakai `stok_id`, bukan `produk_id`, agar alur satu jalur: Produk → Stok → Mutasi Stok.
- Katalog, laporan produksi, laporan stok, laporan penjualan/pembayaran, riwayat produksi, dan riwayat pesanan **tidak dibuat sebagai tabel**; semuanya dihasilkan dari query tabel transaksi.
- `pembayaran.dikonfirmasi_oleh` nullable: diisi untuk Cash yang dikonfirmasi Admin, sedangkan Midtrans dapat memperbarui status otomatis.
