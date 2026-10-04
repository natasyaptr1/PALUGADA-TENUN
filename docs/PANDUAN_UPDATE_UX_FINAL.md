# Panduan Update UX Final Palugada Bandar

Versi ini adalah revisi final tampilan web sebelum melanjutkan aplikasi Android penenun.

## Yang berubah
- Pembeli setelah login langsung diarahkan ke **Koleksi**.
- Tidak ada Beranda/Showcase pembeli terpisah.
- Header pembeli tetap di atas: Koleksi, Pesanan, Keranjang, Akun Saya, profil, Keluar.
- Pencarian produk berada tepat di bawah header pada halaman Koleksi.
- Halaman Koleksi dibuat ringkas tanpa hero/judul besar.
- Login dan registrasi menggunakan ikon mata untuk melihat/sembunyikan kata sandi.
- Tombol kembali di login/registrasi tidak memakai ikon panah.
- Akun Saya hanya berisi profil, tanpa kartu hijau/ringkasan yang berulang.
- Landing page dan footer dirapikan.
- Dashboard admin memprioritaskan Total Pesanan, Pemasukan Bulan Ini, Target Belum Selesai, dan Stok Menipis.
- Menu Admin memisahkan Produk, Stok, dan Target Produksi agar fungsi masing-masing jelas.
- Fitur di luar tahap implementasi saat ini tetap tersedia sebagai halaman kosong, tanpa data palsu.

## Cara memasang patch
1. Ekstrak `palugada-tenun-ux-final-patch.zip`.
2. Copy seluruh isi folder hasil ekstrak ke root project:
   `D:\Laragon\www\palugada-tenun`
3. Pilih **Replace files in the destination**.
4. Jangan mengganti `.env` dan jangan menghapus database.
5. Jalankan:

```powershell
php artisan optimize:clear
php artisan view:clear
php artisan route:clear
php artisan serve
```

Tidak perlu `migrate:fresh`.

## Alur pembeli final
Pengunjung: Landing -> Koleksi -> Masuk/Daftar

Pembeli login: Koleksi -> Detail Produk -> Keranjang -> Pesanan -> Akun Saya

## Arti menu admin
- **Produk**: identitas barang yang dijual (nama, jenis, motif, warna, harga, foto).
- **Stok**: jumlah barang yang sudah siap dijual.
- **Target Produksi**: rencana pembuatan barang yang diberikan kepada penenun.

Alur datanya: Produk -> Target Produksi -> Hasil Produksi Penenun -> Stok bertambah -> Produk tersedia untuk pembeli.
