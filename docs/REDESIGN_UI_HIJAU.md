# Redesign UI Palugada Bandar - Sprint 1

Redesign ini hanya mengubah pengalaman antarmuka dan menambah kenyamanan navigasi tanpa menghapus fungsi Sprint 1.

## Fungsi Sprint 1 yang tetap berjalan

- Registrasi pembeli, login, logout, dan hak akses Admin/Penenun/Pembeli.
- CRUD Produk & Stok oleh Admin.
- Upload foto produk.
- CRUD Data Penenun oleh Admin.
- Katalog publik membaca produk aktif dan stok dari database yang sama.
- REST API dasar tetap menggunakan route dan controller yang sudah ada.
- Area Penenun tetap tersedia sebagai fondasi Sprint 2.

## Perubahan antarmuka

- Beranda/katalog memakai gaya editorial e-commerce warna hijau, ivory, dan beige.
- Header memiliki pencarian, navigasi yang sederhana, serta status menu akun.
- Filter produk berdasarkan jenis dan pencarian kode/nama/motif/warna.
- Detail produk ditampilkan melalui modal tanpa mengubah database.
- Tombol Keranjang/Favorit tetap diberi status Sprint 3 agar pengguna tidak salah mengira fitur sudah aktif.
- Dashboard Admin dibuat lebih ringkas dan menampilkan total Produk, Produk Aktif, Stok, dan Penenun.
- Halaman login dan registrasi menggunakan gaya visual yang sama.
- Pesan validasi registrasi telah dibuat dalam Bahasa Indonesia.

## File yang perlu diganti jika memasang manual

1. `routes/web.php`
2. `app/Http/Controllers/AutentikasiController.php`
3. `resources/views/layouts/app.blade.php`
4. `resources/views/pembeli/katalog.blade.php`
5. `resources/views/autentikasi/masuk.blade.php`
6. `resources/views/autentikasi/daftar.blade.php`
7. `resources/views/admin/beranda.blade.php`
8. `resources/views/admin/produk/index.blade.php`
9. `resources/views/penenun/beranda.blade.php`
10. `public/css/app.css`
11. `public/js/app.js`

Tidak perlu mengubah migration atau database untuk memasang redesign ini.

## Setelah mengganti file

Jalankan:

```powershell
php artisan optimize:clear
php artisan serve
```

Buka:

- Website pembeli: `http://127.0.0.1:8000/katalog`
- Login: `http://127.0.0.1:8000/masuk`
- Registrasi: `http://127.0.0.1:8000/daftar`
- Admin: login menggunakan akun Admin, lalu diarahkan ke dashboard.

## Catatan sprint berikutnya

Tombol keranjang dan favorit sengaja belum melakukan transaksi. Tombol tersebut menunjukkan pesan Sprint 3 agar UI sudah memiliki tempat yang konsisten, tetapi logika transaksi tidak dipalsukan sebelum backlog Sprint 3 dikerjakan.
