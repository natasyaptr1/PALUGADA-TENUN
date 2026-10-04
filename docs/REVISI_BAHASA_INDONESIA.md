# Revisi Bahasa Indonesia

Revisi ini menyelaraskan istilah teknis-domain dengan laporan, Use Case, ERD, dan alur bisnis proyek.

Perubahan utama:
- tabel domain menjadi `pengguna`, `profil_penenun`, `produk`, `target_produksi`, `hasil_produksi`, `stok`, `mutasi_stok`, `keranjang`, `detail_keranjang`, `pesanan`, `detail_pesanan`, `pembayaran`;
- model dan controller domain memakai nama Bahasa Indonesia;
- field form, validasi, variabel Blade, route, endpoint API, dan respons API diselaraskan;
- `keranjang_id` pada `pesanan` diwajibkan sesuai alur final checkout dari keranjang;
- `mutasi_stok` memakai `stok_id` agar alur Produk -> Stok -> Mutasi Stok konsisten;
- tabel laporan/katalog/riwayat tidak dibuat karena berasal dari query;
- tabel dan kolom teknis Laravel/Sanctum tertentu tetap memakai nama standar framework untuk menjaga kompatibilitas.
